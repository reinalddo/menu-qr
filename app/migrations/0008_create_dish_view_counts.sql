CREATE TABLE IF NOT EXISTS dish_view_counts (
    day DATE NOT NULL,
    dish_id INT UNSIGNED NOT NULL,
    views INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (day, dish_id),
    KEY dish_view_counts_dish (dish_id),
    CONSTRAINT dish_view_counts_dish_fk FOREIGN KEY (dish_id) REFERENCES dishes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
