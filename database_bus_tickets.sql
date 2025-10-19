


CREATE TABLE IF NOT EXISTS users (
    id TEXT PRIMARY KEY, -- UUID
    full_name TEXT,
    email TEXT UNIQUE NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('user', 'company', 'admin')),
    password TEXT NOT NULL,
    company_id TEXT, -- NULL olabilir, firma admin için
    balance REAL DEFAULT 1000, -- Sanal kredi (TL cinsinden)
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES bus_companies(id)
);

--Otobüs firmaları
CREATE TABLE IF NOT EXISTS bus_companies (
    id TEXT PRIMARY KEY, -- UUID
    name TEXT UNIQUE NOT NULL,
    logo_path TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Coupons tablosu - İndirim kuponları
CREATE TABLE IF NOT EXISTS coupons (
    id TEXT PRIMARY KEY, -- UUID
    code TEXT NOT NULL,
    discount REAL NOT NULL, -- İndirim oranı (0.1 = %10)
    usage_limit INTEGER NOT NULL,
    expire_date DATETIME NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

--Kullanıcıların kullandığı kuponlar
CREATE TABLE IF NOT EXISTS user_coupons (
    id TEXT PRIMARY KEY, -- UUID
    coupon_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

--Seferler
CREATE TABLE IF NOT EXISTS trips (
    id TEXT PRIMARY KEY, -- UUID
    company_id TEXT NOT NULL,
    departure_city TEXT NOT NULL,
    destination_city TEXT NOT NULL,
    departure_time DATETIME NOT NULL,
    arrival_time DATETIME NOT NULL,
    price REAL NOT NULL, -- TL cinsinden
    capacity INTEGER NOT NULL, -- Toplam koltuk sayısı
    created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES bus_companies(id)
);

--Biletler
CREATE TABLE IF NOT EXISTS tickets (
    id TEXT PRIMARY KEY, -- UUID
    trip_id TEXT NOT NULL,
    user_id TEXT NOT NULL,
    status TEXT DEFAULT 'ACTIVE' NOT NULL CHECK(status IN ('ACTIVE', 'CANCELED', 'EXPIRED')),
    total_price REAL NOT NULL, -- TL cinsinden
    coupon_used TEXT, -- Kullanılan kupon kodu
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

--Rezerve edilen koltuklar
CREATE TABLE IF NOT EXISTS booked_seats (
    id TEXT PRIMARY KEY, -- UUID
    ticket_id TEXT NOT NULL,
    seat_number INTEGER NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id)
);



-- Admin kullanıcısı
INSERT OR IGNORE INTO users (id, full_name, email, role, password, balance) VALUES 
('admin-001', 'Sistem Yöneticisi', 'admin@dvice.com', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 0);

-- Test kullanıcısı
INSERT OR IGNORE INTO users (id, full_name, email, role, password, balance) VALUES 
('user-001', 'Test Kullanıcısı', 'yolcu@dvice.com', 'user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1000);

-- Otobüs firmaları
INSERT OR IGNORE INTO bus_companies (id, name, logo_path) VALUES 
('company-001', 'Metro Turizm', 'images/logos/metro.png'),
('company-002', 'Ulusoy Turizm', 'images/logos/ulusoy.png'),
('company-003', 'Varan Turizm', 'images/logos/varan.png'),
('company-004', 'Pamukkale Turizm', 'images/logos/pamukkale.png');

-- Firma admin kullanıcıları
INSERT OR IGNORE INTO users (id, full_name, email, role, password, company_id, balance) VALUES 
('company-admin-001', 'Metro Admin', 'metro@dvice.com', 'company', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company-001', 0),
('company-admin-002', 'Ulusoy Admin', 'ulusoy@dvice.com', 'company', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'company-002', 0);

-- İndirim kuponları
INSERT OR IGNORE INTO coupons (id, code, discount, usage_limit, expire_date) VALUES 
('coupon-001', 'YENI10', 0.10, 100, '2024-12-31 23:59:59'),
('coupon-002', 'KAMPANYA20', 0.20, 50, '2024-12-31 23:59:59'),
('coupon-003', 'ERKEN15', 0.15, 200, '2024-12-31 23:59:59');

-- Örnek seferler
INSERT OR IGNORE INTO trips (id, company_id, departure_city, destination_city, departure_time, arrival_time, price, capacity) VALUES 
('trip-001', 'company-001', 'İstanbul', 'Ankara', '2024-06-15 08:00:00', '2024-06-15 14:00:00', 150, 45),
('trip-002', 'company-001', 'İstanbul', 'İzmir', '2024-06-15 10:00:00', '2024-06-15 17:00:00', 120, 45),
('trip-003', 'company-002', 'Ankara', 'İstanbul', '2024-06-15 09:00:00', '2024-06-15 15:00:00', 150, 45),
('trip-004', 'company-003', 'İzmir', 'Antalya', '2024-06-15 11:00:00', '2024-06-15 18:00:00', 100, 45),
('trip-005', 'company-004', 'Bursa', 'İstanbul', '2024-06-15 13:00:00', '2024-06-15 15:30:00', 80, 45);
