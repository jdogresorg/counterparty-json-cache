DROP TABLE IF EXISTS dogeparty_json;
CREATE TABLE dogeparty_json (
    id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    testnet        BOOLEAN,                                                     -- indicates if this is a testnet record
    asset_id       INTEGER UNSIGNED,                                            -- id of record in assets
    url            VARCHAR(255),                                                -- URL of the JSON requested
    json           JSON,                                                        -- Actual JSON 
    hash           CHAR(64) NOT NULL,                                           -- sha256sum hash of the JSON data
    created        datetime NOT NULL default now(),                             -- Time record was first created
    updated        datetime NOT NULL default now()                             -- Time record was last updated
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX asset_id ON dogeparty_json (asset_id);
CREATE INDEX created ON dogeparty_json (created);
CREATE INDEX updated ON dogeparty_json (updated);
