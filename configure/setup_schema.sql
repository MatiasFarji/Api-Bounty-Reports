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
    IF ts IS NULL THEN
        ts := clock_timestamp();
    END IF;

    unix_ts_ms := (EXTRACT(EPOCH FROM ts) * 1000)::BIGINT;

    unix_ts_bytes := set_byte(set_byte(set_byte(set_byte(set_byte(set_byte('\x000000000000'::bytea,
        5, ((unix_ts_ms >>  0) & 255)::int),
        4, ((unix_ts_ms >>  8) & 255)::int),
        3, ((unix_ts_ms >> 16) & 255)::int),
        2, ((unix_ts_ms >> 24) & 255)::int),
        1, ((unix_ts_ms >> 32) & 255)::int),
        0, ((unix_ts_ms >> 40) & 255)::int);

    rand_bytes := gen_random_bytes(10);

    result := unix_ts_bytes || rand_bytes;

    result := set_byte(result, 6, ((get_byte(result, 6) & 15) | 112)::int);
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
-- Sources
-- ========================================
CREATE TABLE IF NOT EXISTS sources (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Categories (high level)
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Subcategories (specific)
-- ========================================
CREATE TABLE IF NOT EXISTS subcategories (
    id SERIAL PRIMARY KEY,
    category_id INT NOT NULL REFERENCES categories(id) ON DELETE CASCADE,
    name TEXT NOT NULL,
    UNIQUE(category_id, name),
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Programs
-- ========================================
CREATE TABLE IF NOT EXISTS programs (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Reports
-- ========================================
CREATE TABLE IF NOT EXISTS reports (
    id UUID PRIMARY KEY DEFAULT gen_uuid_v7(),
    source_id INT NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    subcategory_id INT REFERENCES subcategories(id) ON DELETE SET NULL,
    program_id INT REFERENCES programs(id) ON DELETE SET NULL,
    external_id TEXT,
    title TEXT NOT NULL,
    full_text TEXT,
    severity SMALLINT CHECK (severity >= 0 AND severity <= 100),
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
CREATE INDEX IF NOT EXISTS idx_reports_subcategory_id ON reports(subcategory_id);
CREATE INDEX IF NOT EXISTS idx_reports_program_id ON reports(program_id);

CREATE INDEX IF NOT EXISTS idx_reports_search_vector ON reports USING GIN (search_vector);

CREATE INDEX IF NOT EXISTS idx_reports_published_at ON reports(published_at);
CREATE INDEX IF NOT EXISTS idx_reports_severity ON reports(severity);

-- ========================================
-- Ownership
-- ========================================
ALTER TABLE sources OWNER TO :db_user;
ALTER SEQUENCE sources_id_seq OWNER TO :db_user;

ALTER TABLE categories OWNER TO :db_user;
ALTER SEQUENCE categories_id_seq OWNER TO :db_user;

ALTER TABLE subcategories OWNER TO :db_user;
ALTER SEQUENCE subcategories_id_seq OWNER TO :db_user;

ALTER TABLE programs OWNER TO :db_user;
ALTER SEQUENCE programs_id_seq OWNER TO :db_user;

ALTER TABLE reports OWNER TO :db_user;

-- Categorías: nombre único
CREATE UNIQUE INDEX IF NOT EXISTS idx_categories_name
    ON categories (name);

-- Subcategorías: combinación categoría + nombre única
CREATE UNIQUE INDEX IF NOT EXISTS idx_subcategories_category_name
    ON subcategories (category_id, name);