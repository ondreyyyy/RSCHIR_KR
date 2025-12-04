CREATE TABLE IF NOT EXISTS profiles (
    id SERIAL PRIMARY KEY,
    external_id VARCHAR(64) NOT NULL,
    nickname VARCHAR(255) NOT NULL,
    stats_json JSONB NOT NULL DEFAULT '{}'::jsonb,
    created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE UNIQUE INDEX IF NOT EXISTS profiles_external_id_unique
    ON profiles (external_id);


