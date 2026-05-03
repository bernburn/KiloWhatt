-- KiloWhatt PostgreSQL Schema

-- 1. Users Table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(150) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 2. Appliance Presets (Global Database)
CREATE TABLE appliance_presets (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),
    default_watts DECIMAL(10, 2) NOT NULL,
    default_power_factor DECIMAL(4, 2) DEFAULT 1.0,
    default_usage_behavior DECIMAL(5, 2) DEFAULT 100.0,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 3. User Appliances (User's specific entries)
CREATE TABLE user_appliances (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    preset_id INTEGER REFERENCES appliance_presets(id) ON DELETE SET NULL,
    custom_name VARCHAR(100),
    watts DECIMAL(10, 2) NOT NULL,
    hours_per_day DECIMAL(4, 2) NOT NULL,
    usage_behavior_percent DECIMAL(5, 2) NOT NULL,
    power_factor DECIMAL(4, 2) DEFAULT 1.0,
    monthly_kwh DECIMAL(10, 4),
    monthly_cost DECIMAL(10, 2),
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 4. User Bills (For Calibration)
CREATE TABLE user_bills (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    total_kwh DECIMAL(10, 2) NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    computed_rate DECIMAL(10, 4) NOT NULL,
    bill_date DATE DEFAULT CURRENT_DATE,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- 5. Analysis Reports (Gemini Output)
CREATE TABLE analysis_reports (
    id SERIAL PRIMARY KEY,
    user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
    gemini_output TEXT,
    chart_data JSONB,
    created_at TIMESTAMP WITH TIME ZONE DEFAULT CURRENT_TIMESTAMP
);

-- Insert initial admin user (Password: admin123 - MUST BE UPDATED)
-- Note: In a real app, this password would be hashed.
-- INSERT INTO users (name, email, password, role) VALUES ('Admin', 'admin@kilowhatt.com', 'hashed_password_here', 'admin');

-- Insert some default presets
INSERT INTO appliance_presets (name, category, default_watts, default_power_factor, default_usage_behavior) VALUES
('Air Conditioner (Inverter)', 'Cooling', 1200, 0.95, 70.0),
('Air Conditioner (Non-Inverter)', 'Cooling', 1500, 0.85, 95.0),
('Refrigerator', 'Kitchen', 150, 0.90, 45.0),
('Desktop PC (Gaming)', 'Electronics', 500, 0.95, 85.0),
('LED TV (50")', 'Electronics', 100, 1.0, 100.0),
('Electric Fan', 'Cooling', 60, 0.80, 100.0);
