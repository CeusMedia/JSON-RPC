<?php
require_once '../../vendor/autoload.php';
require_once '../../src/Client.php';
require_once '../../src/Caller.php';
new \UI_DevOutput;

$host		= 'localhost';
$path		= 'labs/JsonRpc/demo/server/';

$client		= new \CeusMedia\JsonRpc\Client( $host, $path );
$response	= $client->request( 'date', array( 'format' => 'r' ) );

$caller		= new \CeusMedia\JsonRpc\Caller( $host, $path );
$result		= $caller->date( 'r' );

$code1		= '
$client		= new \CeusMedia\JsonRpc\Client( \''.$host.'\', \''.$path.'\' );
$response	= $client->request( \'date\', array( \'format\' => \'r\' ) );
';

$code2		= '
$caller		= new \CeusMedia\JsonRpc\Caller( \''.$host.'\', \''.$path.'\' );
$result		= $caller->date( \'r\' );
';

$code3		= '
$caller		= new \CeusMedia\JsonRpc\Caller( \''.$host.'\', \''.$path.'\' );
$result		= $caller->notExistingProcedureName();
';

try{
	$caller->notExistingProcedureName( 'r' );
}
catch( \Exception $e ){
	$exception	= \UI_HTML_Exception_View::render( $e );
}

$html	= '
<style>
xmp.code {background-color: rgba(191, 191, 191, 0.25); padding: 1em 2em;};
</style>
<div class="container">
	<h1 class="muted">CeusMedia Component Demo</h1>
	<h2>JSON RPC Client</h2>
	<h3>Using the Client</h3>
	<big><strong>Code</strong></big>
	<xmp class="code">'.trim( $code1 ).'</xmp>
	<big><strong>Result</strong></big>
	'.print_m( $response, NULL, NULL, TRUE ).'
	<br/>
	<p>The information you wanted to know is held in response field "data".</p>
	<hr/>
	<h3>Using the Caller</h3>
	<big><strong>Code</strong></big>
	<xmp class="code">'.trim( $code2 ).'</xmp>
	<big><strong>Result</strong></big>
	'.print_m( $result, NULL, NULL, TRUE ).'
	<hr/>
	<big><strong>Invalid Method</strong></big>
	<p>If called procedure is not existing, an exception will be thrown.</p>
	<big><strong>Code</strong></big>
	<xmp class="code">'.trim( $code3 ).'</xmp>
	<big><strong>Result</strong></big>
	<div style="border: 1px solid #ccc; padding: 1em 2em">
		'.$exception.'
	</div>
</div>
';

$page	= new \UI_HTML_PageFrame();
$page->addStylesheet( 'https://cdn.ceusmedia.de/css/bootstrap.min.css' );
$page->addBody( $html );
print( $page->build() );
