<?php

if(!function_exists('custom_array_key')){
    /**
     * 验证参数
     * @param array $array
     * @param string $string
     * @param bool $status
     * @return bool
     */
    function custom_array_key(array $array,string $string,bool $status = FALSE) : bool {
        if(!empty($array) && !empty($string)){
            $keys = explode(",",$string);

            if($status){
                foreach($keys as $val){
                    if(!isset($array[$val]) || empty($array[$val])){
                        return false;
                    }
                }
            }else{
                foreach($keys as $val){
                    if(!isset($array[$val])){
                        return false;
                    }
                }
            }
            return true;
        }

        return false;
    }
}

if(!function_exists('custom_return_success')){
    /**
     * 返回正确
     * @param null $msg
     * @param null $data
     * @return array
     */
    function custom_return_success($msg = null,$data=null) : array{
        return [
            'code' => 200,
            'msg'  => $msg ?? 'success',
            'data' => $data
        ];
    }
}

if(!function_exists('custom_return_error')){
    /**
     * 返回失败
     * @param null $msg
     * @param null $data
     * @return array
     */
    function custom_return_error($msg = null,$data=null) : array {
        return [
            'code' => 500,
            'msg'  => $msg ?? 'error',
            'data' => $data
        ];
    }
}