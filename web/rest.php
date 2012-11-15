<?php
ini_set('display_errors', 0);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

$loader = require __DIR__ . '/../vendor/autoload.php';
$loader->add('Musicbozz',  __DIR__.'/../src/logic/');
$loader->add('Sapo',  __DIR__.'/../src/logic/');
$loader->register();

use Musicbozz\Question;
use Musicbozz\Question_Type;
$question = Question::factory(Question_Type::getRandom());

header("Content-type: text/xml; charset=utf-8");
?>
<question>
	<url><![CDATA[<?php print $question->trackPreview; ?>]]></url>
	<type><?php print $question->type; ?></type>
	<solutions>
		<?php foreach ($question->solutions as $solution) { ?>
		<solution><![CDATA[<?php print $solution; ?>]]></solution>
		<?php } ?>
	</solutions>
	<correct><?php print $question->correct; ?></correct>
</question>