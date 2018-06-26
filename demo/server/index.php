<?php
require_once '../../vendor/autoload.php';
require_once '../../src/Server.php';
new \UI_DevOutput;

class Service{
	public function date( $format = "c" ){
		return date( $format );
	}
}

$request	= new \Net_HTTP_Request_Receiver();
$server		= new \CeusMedia\JsonRpc\Server();
$server->setSerializeExceptions( TRUE );
$server->loadClass( 'Service' );
$server->handle( $request );
