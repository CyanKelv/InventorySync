-- #!mysql
-- #{ sync

-- #  { init
CREATE TABLE IF NOT EXISTS inventories (
    uuid VARCHAR(50) NOT NULL ,
    inventory BLOB ,
    armor BLOB,
    PRIMARY KEY (uuid)
);
-- #  }

-- #  { save
-- #    :uuid string
-- #    :inventory string
-- #    :armor string
INSERT INTO inventories (uuid, inventory, armor) VALUES (:uuid, :inventory, :armor) ON DUPLICATE KEY UPDATE inventory = VALUES(inventory), armor = VALUES(armor);
-- #  }

-- #  { load
-- #    :uuid string
SELECT inventory, armor FROM inventories WHERE uuid = :uuid;
-- #  }

-- #  { delete
-- #    :uuid string
DELETE FROM inventories WHERE uuid = :uuid;
-- #  }

-- # }