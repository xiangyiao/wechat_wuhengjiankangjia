<?php
/**
 * Created by PhpStorm.
 * User: yuxi
 * Date: 2021-02-04
 * Time: 13:14
 */
/**
 * error code 说明.
 * <ul>
 * <li>-41001: encodingAesKey 非法</li>
 * <li>-41003: aes 解密失败</li>
 * <li>-41004: 解密后得到的buffer非法</li>
 * <li>-41005: base64加密失败</li>
 * <li>-41016: base64解密失败</li>
 * </ul>
 */
class ErrorCode
{
    public static $OK = 0;
    public static $IllegalAesKey = -41001; //非法密钥
    public static $IllegalIv = -41002; //非法初始向量
    public static $IllegalBuffer = -41003; //非法密文
    public static $DecodeBase64Error = -41004; //解码错误
}