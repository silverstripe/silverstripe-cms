<?php
/**
 * Create a process in session which is incremented to calls from the client
 */
class BatchProcess extends Object {
	
	protected $objects;
	protected $current;
	protected $id;
	protected $scriptOutput = true;
	
	function __construct( $collection ) {

		$this->current = 0;
	    
	    if( $collection ) {
		  if( is_array( $collection ) )
			  $this->objects = $collection;
		  elseif( is_a( $collection, 'DataObjectSet' ) ) {
			  $this->objects = $collection->toArray();

		  } else
			  $this->objects = array( $collection );
	    }		
  }
	
	function runToCompletion() {
		$this->scriptOutput = false;
		$this->current = 0;
		$ignore = $this->next( count( $this->objects ) );
		
		$this->complete();
	}
	
	function getID() {
		return $this->id;
	}
	
	function next() {
		self::addProcess( $this );
		return $this->id.':'.$this->current.'/'.count( $this->objects );
	}
	
	function start() {
		$this->current = 0;
		$this->id = self::generateID(); 
    
    if( !$this->objects || count( $this->objects ) === 0 )
      return $this->complete();
    
		return $this->next();
	}
	
	function complete() {
		self::removeProcess( $this );
	}
	
	static function generateID() {
		return count(Session::get('BatchProcesses')) + 1;
	}
	
	static function addProcess( $process ) {
		Session::set('BatchProcesses.' . ($process->getID() - 1), serialize($process));
	}
	
	static function removeProcess( $process ) {
		Session::clear('BatchProcesses.' . ($process->getID() - 1));
	}
}

class BatchProcess_Controller extends Controller {
	
	function next() {
		
		$processID = $this->urlParams['ID'];
		
		if( !$processID ) {
			return "ERROR:Could not continue process";
		}
		
		$process = unserialize(Session::get('BatchProcesses.' . ($this->urlParams['ID'] - 1)));
		
		if( !$process ) {
			return "ERROR:Could not continue process";
		}
		
		if( $this->urlParams['Batch'] )
			return $process->next( $this->urlParams['Batch'] );
		else
			return $process->next();
	}	
}
?>
