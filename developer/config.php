<?php
/*Configurar */
define('host','ec2-54-211-176-156.compute-1.amazonaws.com');
define('user','xjndbvsvxponhw');
define('pass','8ce80b9ab38f119be3277dfbe6f4ec6632e0fc2e552b658a25ac99e65da309ca');
define('dbname','d5bfh6qdmeku9j');

/*MySQL*/
# define('connstring','mysql:host='.host.';dbname='.dbname.';charset=utf8');
/*pgSQL*/
define('connstring','pgsql:host='.host.';port=5432;dbname='.dbname);
?>