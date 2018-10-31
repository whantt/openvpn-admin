<?php
namespace app\admin\model;

use think\Config;
use think\Db;
use think\Loader;
use think\Model;
use traits\model\SoftDelete;

class Key extends Admin
{
	use SoftDelete;
    protected $deleteTime = 'delete_time';
    public $openVPNPath = 'D:\\Program Files\\OpenVPN\\';
    public $openVPNCMDPath = 'D:\\"Program Files"\\OpenVPN\\';

	public function getList( $request )
	{
		$request = $this->fmtRequest( $request );
		$data = $this->order('create_time desc')->where( $request['map'] )->limit($request['offset'], $request['limit'])->select();
//		$data = $this->order('create_time desc')->select();
//		return $this->_fmtData( $data );
        return $data;
	}

	public function saveData( $data )
	{
//		if( isset( $data['id']) && !empty($data['id'])) {
//			$result = $this->edit( $data );
//		} else {
			$result = $this->add( $data );
//		}
		return $result;
	}


	public function add(array $data = [])
	{
//        $cmd = '"D:\Program Files\CCProxy\CCProxy.exe" -u..';
//        if( PHP_OS=='WINNT' && version_compare(PHP_VERSION, '5.3.0', '<') ){
//            $cmd = '"'.$cmd.'"';    //解决路径空格问题
//        }
//        $lastLine = exec ( $cmd ,$output, $return_val);
//        $lastLine = exec("openssl req -days 3650 -nodes -new -keyout 1.key -out 1.csr -config easy-rsa\openssl-1.0.0.cnf -subj /C=CN/ST=BJ/L=CY/O=AVIC/OU=dev/CN=%1/emailAddress=songzc@avic-digital.com", $output, $return_val);

        //读文件 begin
        $confFilePath = "easy-rsa\\client.ovpn";
        $caFilePath = $this->openVPNPath."easy-rsa\\keys\\"."ca.crt";
        $crtFilePath = $this->openVPNPath."easy-rsa\\keys\\".$data["user_id"].".crt";
        $keyFilePath = $this->openVPNPath."easy-rsa\\keys\\".$data["user_id"].".key";
        $ovpnFilePath = "easy-rsa\\keys\\".$data["user_id"].".ovpn";
        if(!file_exists($crtFilePath)){
            $cmd = $this->openVPNCMDPath."easy-rsa\build-key ".$data["user_id"]; //生成证书脚本
            $lastLine = exec($cmd, $output, $return_val);
        }
        $confFile = fopen($confFilePath, "r") or die("Unable to open file!");
        $caFile = fopen($caFilePath, "r") or die("Unable to open file!");
        $crtFile = fopen($crtFilePath, "r") or die("Unable to open file!");
        $keyFile = fopen($keyFilePath, "r") or die("Unable to open file!");
        $ovpnFile = fopen($ovpnFilePath, "w") or die("Unable to write file!");
        $confFileStr = fread($confFile,filesize($confFilePath));
        $caFileStr = fread($caFile,filesize($caFilePath));
        $crtFileStr = fread($crtFile,filesize($crtFilePath));
        $keyFileStr = fread($keyFile,filesize($keyFilePath));
        $content = $confFileStr;
        $content .= "\n<ca>\n";
        $content .= $caFileStr;
        $content .= "</ca>";
        $content .= "\n<cert>\n";
        $content .= $crtFileStr;
        $content .= "</cert>";
        $content .= "\n<key>\n";
        $content .= $keyFileStr;
        $content .= "</key>";
        $data['path'] =  "\\".$ovpnFilePath;
        $data['content'] =  $content;
        fwrite($ovpnFile, $content);
        fclose($caFile);
        fclose($crtFile);
        fclose($keyFile);
        fclose($confFile);
        fclose($ovpnFile);
        //读文件 end
		$data['create_time'] = time();
//        $data['note'] = $return_val;
		$this->allowField(true)->save($data);
		if($this->id > 0){
            return info(lang('Add succeed'), 0);
        }else{
            return info(lang('Add failed') ,0);
        }
	}

	public function edit(array $data = [])
	{
		$userValidate = validate('User');
		if(!$userValidate->scene('edit')->check($data)) {
			return info(lang($userValidate->getError()), 4001);
		}
		$moblie = $this->where(['mobile'=>$data['mobile']])->where('id', '<>', $data['id'])->value('mobile');
		if (!empty($moblie)) {
			return info(lang('Mobile already exists'), 0);
		}

		if($data['password2'] != $data['password']){
            return info(lang('The two passwords No match!'),0);
        }
        $data['update_time'] = time();

		$data['password'] = mduser($data['password']);
		$res = $this->allowField(true)->save($data,['id'=>$data['id']]);
		if($res == 1){
            return info(lang('Edit succeed'), 1);
        }else{
            return info(lang('Edit failed'), 0);
        }
	}

	public function deleteById($id)
	{
		$result = Key::destroy($id);
		if ($result > 0) {
            return info(lang('Delete succeed'), 1);
        }   
	}

	//格式化数据
	private function _fmtData( $data )
	{
		if(empty($data) && is_array($data)) {
			return $data;
		}

		foreach ($data as $key => $value) {
			$data[$key]['create_time'] = date('Y-m-d H:i:s',$value['create_time']);
			$data[$key]['status'] = $value['status'] == 1 ? lang('Start') : lang('Off');
		}

		return $data;
	}

}
