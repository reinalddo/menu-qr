CREATE TABLE IF NOT EXISTS dish_sizes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    dish_id INT UNSIGNED NOT NULL,
    name VARCHAR(60) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    position INT NOT NULL DEFAULT 0,
    KEY dish_sizes_dish_position (dish_id, position),
    CONSTRAINT dish_sizes_dish_fk FOREIGN KEY (dish_id) REFERENCES dishes (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
