DROP TABLE IF EXISTS counterparty_json;
CREATE TABLE counterparty_json (
    id             INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    testnet        BOOLEAN,                                                     -- indicates if this is a testnet record
    asset_id       INTEGER UNSIGNED,                                            -- id of record in assets
    json           JSON,                                                        -- Actual JSON 
    hash           CHAR(64) NOT NULL,                                           -- sha256sum hash of the JSON data
    created        datetime NOT NULL default now(),                             -- Time record was first created
    updated        datetime NOT NULL default now()                             -- Time record was last updated
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

CREATE INDEX asset_id ON counterparty_json (asset_id);
CREATE INDEX created ON counterparty_json (created);
CREATE INDEX updated ON counterparty_json (updated);
