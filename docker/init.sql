-- Bookoholik: Home Library Database Initialization
-- This script runs when the PostgreSQL container is first created

-- Enable UUID extension
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Users table
CREATE TABLE users (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    username VARCHAR(100) UNIQUE NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('admin', 'user', 'viewer')),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Books table
CREATE TABLE books (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    title VARCHAR(500) NOT NULL,
    author VARCHAR(500) NOT NULL,
    genre VARCHAR(100),
    publication_year INTEGER,
    language VARCHAR(50) DEFAULT 'arabic' CHECK (language IN ('arabic', 'english', 'french', 'other')),
    location_room VARCHAR(100),
    location_shelf VARCHAR(100),
    read_status BOOLEAN DEFAULT FALSE,
    isbn VARCHAR(20),
    edition_house VARCHAR(255),
    num_pages INTEGER,
    series_name VARCHAR(255),
    series_position INTEGER,
    notes TEXT,
    cover_image_url VARCHAR(500),
    owner_id UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Lending records table
CREATE TABLE lending_records (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    book_id UUID NOT NULL REFERENCES books(id) ON DELETE CASCADE,
    borrower_name VARCHAR(255) NOT NULL,
    borrower_contact VARCHAR(255),
    lent_date DATE NOT NULL DEFAULT CURRENT_DATE,
    due_date DATE NOT NULL,
    returned_date DATE,
    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'returned', 'overdue')),
    notes TEXT,
    lent_by UUID REFERENCES users(id) ON DELETE SET NULL,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Genres reference table
CREATE TABLE genres (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    name_ar VARCHAR(100),
    name_fr VARCHAR(100)
);

-- Writers table
CREATE TABLE writers (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    name VARCHAR(255) NOT NULL,
    name_ar VARCHAR(255),
    name_fr VARCHAR(255),
    nationality VARCHAR(100),
    birth_year INTEGER,
    death_year INTEGER,
    biography TEXT,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE UNIQUE INDEX idx_writers_name ON writers(name);
CREATE INDEX idx_writers_name_ar ON writers(name_ar);

-- Audit log table
CREATE TABLE audit_log (
    id UUID PRIMARY KEY DEFAULT uuid_generate_v4(),
    user_id UUID REFERENCES users(id) ON DELETE SET NULL,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id UUID,
    details JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

-- Indexes for performance
CREATE INDEX idx_books_title ON books USING gin(to_tsvector('simple', title));
CREATE INDEX idx_books_author ON books USING gin(to_tsvector('simple', author));
CREATE INDEX idx_books_genre ON books(genre);
CREATE INDEX idx_books_language ON books(language);
CREATE INDEX idx_books_publication_year ON books(publication_year);
CREATE INDEX idx_books_read_status ON books(read_status);
CREATE INDEX idx_books_isbn ON books(isbn);
CREATE INDEX idx_books_owner ON books(owner_id);
CREATE INDEX idx_lending_book ON lending_records(book_id);
CREATE INDEX idx_lending_status ON lending_records(status);
CREATE INDEX idx_lending_due_date ON lending_records(due_date);
CREATE INDEX idx_audit_user ON audit_log(user_id);
CREATE INDEX idx_audit_entity ON audit_log(entity_type, entity_id);

-- Insert default genres
INSERT INTO genres (name, name_ar, name_fr) VALUES
    ('Fiction', 'رواية', 'Fiction'),
    ('Non-Fiction', 'غير خيالي', 'Non-fiction'),
    ('Science', 'علوم', 'Sciences'),
    ('History', 'تاريخ', 'Histoire'),
    ('Philosophy', 'فلسفة', 'Philosophie'),
    ('Religion', 'دين', 'Religion'),
    ('Poetry', 'شعر', 'Poésie'),
    ('Biography', 'سيرة ذاتية', 'Biographie'),
    ('Technology', 'تكنولوجيا', 'Technologie'),
    ('Art', 'فن', 'Art'),
    ('Literature', 'أدب', 'Littérature'),
    ('Education', 'تعليم', 'Éducation'),
    ('Politics', 'سياسة', 'Politique'),
    ('Economics', 'اقتصاد', 'Économie'),
    ('Psychology', 'علم نفس', 'Psychologie'),
    ('Self-Help', 'تطوير ذاتي', 'Développement personnel'),
    ('Children', 'أطفال', 'Enfants'),
    ('Travel', 'رحلات', 'Voyages'),
    ('Cooking', 'طبخ', 'Cuisine'),
    ('Islamic Studies', 'دراسات إسلامية', 'Études islamiques');

-- Insert default admin user
-- DEFAULT PASSWORD: Admin1234! (MUST be changed on first login)
INSERT INTO users (username, email, password_hash, full_name, role) VALUES
    ('admin', 'admin@homelibrary.local', '$2y$12$/COW4ljVYn.YoMpWPrxtgu4dyeNo73abitkJiqab8LEUn.qqq3D3e', 'مدير المكتبة', 'admin');
