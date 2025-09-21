-- ========================================
-- Extensions
-- ========================================
CREATE EXTENSION IF NOT EXISTS "pgcrypto"; -- for gen_random_bytes()

-- ========================================
-- UUIDv7 generator
-- ========================================
CREATE OR REPLACE FUNCTION gen_uuid_v7(ts TIMESTAMP WITH TIME ZONE DEFAULT NULL)
RETURNS uuid AS $$
DECLARE
    unix_ts_ms BIGINT;
    unix_ts_bytes BYTEA;
    rand_bytes BYTEA;
    result BYTEA;
BEGIN
    -- If no timestamp is provided, use now()
    IF ts IS NULL THEN
        ts := clock_timestamp();
    END IF;

    -- Convert timestamp to milliseconds since epoch
    unix_ts_ms := (EXTRACT(EPOCH FROM ts) * 1000)::BIGINT;

    -- timestamp as 6 bytes (48 bits)
    unix_ts_bytes := set_byte(set_byte(set_byte(set_byte(set_byte(set_byte('\x000000000000'::bytea,
        5, ((unix_ts_ms >>  0) & 255)::int),
        4, ((unix_ts_ms >>  8) & 255)::int),
        3, ((unix_ts_ms >> 16) & 255)::int),
        2, ((unix_ts_ms >> 24) & 255)::int),
        1, ((unix_ts_ms >> 32) & 255)::int),
        0, ((unix_ts_ms >> 40) & 255)::int);

    -- random 10 bytes
    rand_bytes := gen_random_bytes(10);

    -- concat timestamp + random
    result := unix_ts_bytes || rand_bytes;

    -- set version (bits 48-51 → 0111 for v7)
    result := set_byte(result, 6, ((get_byte(result, 6) & 15) | 112)::int);

    -- set variant (bits 64-65 → 10)
    result := set_byte(result, 8, ((get_byte(result, 8) & 63) | 128)::int);

    RETURN encode(result, 'hex')::uuid;
END;
$$ LANGUAGE plpgsql VOLATILE;


-- ========================================
-- Schema ownership
-- ========================================
ALTER SCHEMA public OWNER TO :db_user;

ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO :db_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO :db_user;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON FUNCTIONS TO :db_user;

-- ========================================
-- Sources table
-- ========================================
CREATE TABLE IF NOT EXISTS sources (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Categories table
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Programs table
-- ========================================
CREATE TABLE IF NOT EXISTS programs (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Reports table
-- ========================================
CREATE TABLE IF NOT EXISTS reports (
    id UUID PRIMARY KEY DEFAULT gen_uuid_v7(), -- ✅ UUIDv7
    source_id INT NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    program_id INT REFERENCES programs(id) ON DELETE SET NULL,
    external_id TEXT,
    title TEXT NOT NULL,
    full_text TEXT,
    severity TEXT,
    report_url TEXT,
    published_at TIMESTAMP,
    scraped_at TIMESTAMP DEFAULT now(),

    search_vector tsvector
        GENERATED ALWAYS AS (to_tsvector('english', coalesce(title,'') || ' ' || coalesce(full_text,''))) STORED
);

-- ========================================
-- Indexes
-- ========================================
CREATE INDEX IF NOT EXISTS idx_reports_source_id ON reports(source_id);
CREATE INDEX IF NOT EXISTS idx_reports_category_id ON reports(category_id);
CREATE INDEX IF NOT EXISTS idx_reports_program_id ON reports(program_id);

CREATE INDEX IF NOT EXISTS idx_reports_search_vector ON reports USING GIN (search_vector);

CREATE INDEX IF NOT EXISTS idx_reports_published_at ON reports(published_at);
CREATE INDEX IF NOT EXISTS idx_reports_severity ON reports(severity);

-- ========================================
-- Transfer ownership to app user
-- ========================================
ALTER TABLE sources OWNER TO :db_user;
ALTER SEQUENCE sources_id_seq OWNER TO :db_user;

ALTER TABLE categories OWNER TO :db_user;
ALTER SEQUENCE categories_id_seq OWNER TO :db_user;

ALTER TABLE programs OWNER TO :db_user;
ALTER SEQUENCE programs_id_seq OWNER TO :db_user;

ALTER TABLE reports OWNER TO :db_user;
