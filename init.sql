CREATE DATABASE lish;

\c lish;

CREATE TABLE links (
    id SERIAL PRIMARY KEY,
    link character varying
);