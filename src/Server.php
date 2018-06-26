<?php
/**
 *	...
 *
 *	Copyright (c) 2011-2018 Christian Würker (ceusmedia.de)
 *
 *	This program is free software: you can redistribute it and/or modify
 *	it under the terms of the GNU General Public License as published by
 *	the Free Software Foundation, either version 3 of the License, or
 *	(at your option) any later version.
 *
 *	This program is distributed in the hope that it will be useful,
 *	but WITHOUT ANY WARRANTY; without even the implied warranty of
 *	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *	GNU General Public License for more details.
 *
 *	You should have received a copy of the GNU General Public License
 *	along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *	@category		Library
 *	@package		CeusMedia_Common_Net_RPC_JSON
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2011-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/JsonRpc
 */
namespace CeusMedia\JsonRpc;
/**
 *	...
 *
 *	@category		Library
 *	@package		CeusMedia_Common_Net_RPC_JSON
 *	@author			Christian Würker <christian.wuerker@ceusmedia.de>
 *	@copyright		2011-2018 Christian Würker
 *	@license		http://www.gnu.org/licenses/gpl-3.0.txt GPL 3
 *	@link			https://github.com/CeusMedia/JsonRpc
 */
class Server {

	protected $procedures	= array();
	protected $serializeExceptions	= FALSE;
	protected $log;
	protected $keyProcedure	= 'proc';
	protected $keyArguments	= 'args';

	public function __construct(){}

	protected function evaluateArguments( $procedureName, $arguments ){
		if( empty( $this->procedures[$procedureName] ) )
			throw new \InvalidArgumentException( 'Procedure "'.$procedureName.'" is not available' );
		$index		= array_keys( $this->procedures[$procedureName]['parameters'] );
		$defined	= array_values( $this->procedures[$procedureName]['parameters'] );
		for( $i=count( $defined )-1; $i>=0; $i--){
			if( !$defined[$i]['optional'] && count( $arguments ) < $i + 1 )
				throw new \InvalidArgumentException( 'Argument for parameter "'.$index[$i].'" is missing' );
		}
		$list	= array();
		foreach( $arguments as $key => $value )
			if( in_array( $key, $index ) )
				$list[$key]	= $value;
		return $list;
	}

	public function handle( \ADT_List_Dictionary $request ){
		$buffer		= new \UI_OutputBuffer();
		$time1		= microtime( TRUE );
		try{
			$procedureName	= $request->get( $this->keyProcedure );
			$arguments		= $request->get( $this->keyArguments );
			if( !strlen( trim( $procedureName ) ) )
				throw new \InvalidArgumentException( 'No procedure ('.$this->keyProcedure.') given' );
			if( !is_array( $arguments ) )
				$arguments	= array();
			if( !array_key_exists( $procedureName, $this->procedures ) )
				throw new \InvalidArgumentException( 'Procedure "'.$procedureName.'" is not available' );
			$procedure	= $this->procedures[$procedureName];
			$className	= $procedure['className'];
			$arguments	= $this->evaluateArguments( $procedureName, $arguments );
			$time1		= microtime( TRUE );
			$class		= new \ReflectionClass( $className );
			$object		= $class->newInstance();
			$procedure	= $class->getMethod( $procedureName );
			$result		= $procedure->invokeArgs( $object, $arguments );
			$time2		= microtime( TRUE );
			$data		= array(
				'status'		=> 'data',
				'data'			=> $result,
				'timeStart'		=> $time1,
				'timeStop'		=> $time2,
				'timeProc'	=> round( $time2 - $time1, 3 )									//  duration of procedure call in seconds with max 3 decimal places
			);
		}
		catch( \Exception $e ){
			$time2		= microtime( TRUE );
			$data		= array(
				'status'		=> 'error',
				'data'			=> $e->getMessage(),
				'timeStart'		=> $time1,
				'timeStop'		=> $time2,
				'timeProc'		=> 0,															//  duration of procedure call
			);
			if( $this->serializeExceptions && $e instanceof \Exception_Serializable)
				$data['serial']	= serialize( $e );
		}
		$data['stdout']		= $buffer->has() ? $buffer->get( TRUE ) : NULL;
		$this->respond( $data );
		return TRUE;
	}

	protected function respond( $data ){
		$response	= new \Net_HTTP_Response;
		$response->setBody( json_encode( $data ) );
		$response->send();
	}

	public function loadClass( $className ){
		$buffer		= new \UI_OutputBuffer();
		try{
			$procedureList	= get_class_methods( $className );
			foreach( $procedureList as $procedureName ){
				$parameters	= array();
				$procedure	= new \ReflectionMethod( $className, $procedureName );
				foreach( $procedure->getParameters() as $parameter )
					$parameters[$parameter->name]	= array(
						'optional'	=> $parameter->isOptional(),
						'default'	=> $parameter->isOptional() ? $parameter->getDefaultValue() : NULL
					);
				if( isset( $this->procedures[$procedureName] ) ){
					$message	= 'Procedure "%1$s" is already defined by class "%2$s"';
					throw new \DomainException( vsprintf( $message, array(
						$procedureName,
						$this->procedures[$procedureName]['className'],
					) ) );
				}
				$this->procedures[$procedureName]	= array(
					'className'		=> $className,
					'parameters'	=> $parameters,
				);
			}
		}
		catch( \Exception $e ){
			$data		= array(
				'status'		=> 'error',
				'data'			=> $e->getMessage(),
				'timeStart'		=> 0,
				'timeStop'		=> 0,
				'timeProc'		=> 0,															//  duration of procedure call
			);
			if( $this->serializeExceptions && $e instanceof \Exception_Serializable)
				$data['serial']	= serialize( $e );
			$data['stdout']		= $buffer->has() ? $buffer->get( TRUE ) : NULL;
			$this->respond( $data );
			exit;
		}
	}

	public function setSerializeExceptions( $boolean ){
		$this->serializeExceptions	= (bool) $boolean;
	}

	public function setProcedureKey( $key ){
		$this->keyProcedure	= $key;
	}

	public function setArgumentsKey( $key ){
		$this->keyArguments	= $key;
	}
}
?>
