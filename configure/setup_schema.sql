-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- ========================================
-- Sources table
-- ========================================
CREATE TABLE IF NOT EXISTS sources (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,          -- e.g. "HackerOne", "Bugcrowd"
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Categories table
-- ========================================
CREATE TABLE IF NOT EXISTS categories (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,          -- e.g. "XSS", "SQL Injection"
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Programs table
-- ========================================
CREATE TABLE IF NOT EXISTS programs (
    id SERIAL PRIMARY KEY,
    name TEXT NOT NULL UNIQUE,          -- e.g. "Yahoo", "Uber"
    created_at TIMESTAMP DEFAULT now()
);

-- ========================================
-- Reports table
-- ========================================
CREATE TABLE IF NOT EXISTS reports (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(), -- replace with UUIDv7 in app layer
    source_id INT NOT NULL REFERENCES sources(id) ON DELETE CASCADE,
    category_id INT REFERENCES categories(id) ON DELETE SET NULL,
    program_id INT REFERENCES programs(id) ON DELETE SET NULL,
    external_id TEXT,                     -- report ID from source
    title TEXT NOT NULL,
    full_text TEXT,                       -- full report body (line breaks allowed)
    severity TEXT,
    report_url TEXT,
    published_at TIMESTAMP,
    scraped_at TIMESTAMP DEFAULT now(),

    -- tsvector column automatically generated from full_text + title
    search_vector tsvector
        GENERATED ALWAYS AS (to_tsvector('english', coalesce(title,'') || ' ' || coalesce(full_text,''))) STORED
);

-- ========================================
-- Indexes
-- ========================================
-- Optimize foreign key lookups
CREATE INDEX IF NOT EXISTS idx_reports_source_id ON reports(source_id);
CREATE INDEX IF NOT EXISTS idx_reports_category_id ON reports(category_id);
CREATE INDEX IF NOT EXISTS idx_reports_program_id ON reports(program_id);

-- Optimize search queries (full-text search)
CREATE INDEX IF NOT EXISTS idx_reports_search_vector ON reports USING GIN (search_vector);

-- Optimize common filters
CREATE INDEX IF NOT EXISTS idx_reports_published_at ON reports(published_at);
CREATE INDEX IF NOT EXISTS idx_reports_severity ON reports(severity);
