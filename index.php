<?php 
/**
 * php 默认使用文件存储session,如果并发量大，效率非常低。而redis对高并发的支持非常的好，所以可以使用redis代替文件存储session.
 *open 当session打开的时候调用次函数，接受两个参数，第一个参数是保持session的路径，第二个参数是session的名字
 *clone 当session 操作完成的时候调用次函数，不接受参数
 *read 以session ID 作为参数，通过SESSION ID 从存储方获得数据，并且反悔，如果数据为空，可以返回一个空字符串,此函数在调换用session_start之前被触发.
 *write 当函数别调用时，有两个参数，一个是sessionid 一个是session的数据
 *destory 当调用session_destory 时触发destory函数，只有一个参数sessionid
 *gc 当php执行session垃圾回收机制的时候触发
 *   在使用函数session_set_sace_handler 之前先把php.ini 中 session_sace_handler = true ,
 */

class SessionManager{
	private $redis;
	private $sessionSavePath;
	private $sessionName;
	private $sessionExpireTime = 30;
	private $host = '127.0.0.1';
	private $port = 6379;


	public function __construct(){
		$this->redis = new redis();
		$this->redis->connect($this->host, $this->port);

		$retval = session_set_sace_handler(
			array($this, "open"),
			array($this, "close"),
			array($this, "read"),
			array($this, "write"),
			array($this, "destroy"),
			array($this, "gc")
		);
		session_start();
	}

	public function open($path, $name){
		return true;
	}

	public function close(){
		return true;
	}

	public function read($id){
		$value = $this->redis->get($id);
		if($value){
			return $value;
		}else{
			return '';
		}
	}


	public function write($id, $data){
		if($this->redis->set($id,$data)){
			$this->redis->expire($id,$this->sessionExpireTime);
			return true;
		}
		return false;
	}

	public function destroy($id){
		if($this->redis->del($id)){
			return true;
		}
		return false;
	}

	public function gc($maxlifetime){
		return true;
	}

	public function __destruct(){
		session_write_close();
	}
}
// other file content set a session
new SessionManager();
$_SESSION['username'] = 'name';

// another file content get sesion
new SessionManager();
echo $_SESSION['username'];


 ?>