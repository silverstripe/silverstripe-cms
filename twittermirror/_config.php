<?php
// Your Twitter asccount name
TwitterMirror_Controller::setTwitteruser('bemorehuman');
// The coresponding password
TwitterMirror_Controller::setTwitterpass('OkRC2YRKSXFo');
// mirror tweets containing this seachterm
//TwitterMirror_Controller::setSearchterm('#bemorehuman');
TwitterMirror_Controller::setSearchterm('silverstripe');

/* Set the level for logging
* Valid values are 0 .. 6
* Where 6 turns the logging off
* 		0 => 'DEBUG',
* 		1 => 'INFO',
* 		2 => 'NOTICE',
* 		3 => 'WARNING',
* 		4 => 'ERROR',
* 		5 => 'CRITICAL',
* 		6 => 'Turn logging off'
*/
TwitterMirror_Controller::setLogThis(1);

?>
