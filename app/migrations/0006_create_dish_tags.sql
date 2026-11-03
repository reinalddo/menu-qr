CREATE TABLE IF NOT EXISTS dish_tags (
    dish_id INT UNSIGNED NOT NULL,
    tag VARCHAR(32) NOT NULL,
    PRIMARY KEY (dish_id, tag),
    CONSTRAINT dish_tags_dish_fk FOREIGN KEY (dish_id) REFERENCES dishes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
