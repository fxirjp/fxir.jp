<?php
/* admin/config.php としてコピーし、鍵を変更すること。config.php は .htaccess で配信拒否。 */
return ['key' => 'CHANGE_ME_' . bin2hex(random_bytes(8))];
