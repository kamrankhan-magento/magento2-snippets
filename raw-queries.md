### Reset customer password
```
UPDATE `customer_entity`
SET `password_hash` = CONCAT(SHA2('xxxxxxxxPASSWORD', 256), ':xxxxxxxx:1')
WHERE `entity_id` = 1916;
```
