<?php
$db = new PDO('sqlite:C:/xampp/htdocs/MARK1/db/mark1.sqlite');
$result = $db->query('PRAGMA integrity_check')->fetchAll();
print_r($result);
?>
```

