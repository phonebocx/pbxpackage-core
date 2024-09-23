DROP TABLE IF EXISTS {{table_name}};
CREATE TABLE {{table_name}}(id INTEGER PRIMARY KEY AUTOINCREMENT, eta integer NOT NULL, item text NOT NULL, serialized integer null, ref text NULL, lock text NULL, lockexpiry integer null);
CREATE INDEX idx01 on {{table_name}} (ref);