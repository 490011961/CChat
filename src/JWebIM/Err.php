<?php
namespace JWebIM;

/**
 * 错误码
 *
 * @author zengqingyun
 * @package JWebIM
 * @namespace JWebIM
 * @version 1.0.0
 * @copyright 2007-2016 聚橙网
 */
class Err
{

    /**
     * 错误
     *
     * @var integer
     */
    const ERR = 0x0001;

    /**
     * 异常
     *
     * @var integer
     */
    const EX = 0x0002;

    /**
     * 解析错误
     *
     * @var integer
     */
    const ERR_PARSE = 0x0011;

    /**
     * 算术错误
     *
     * @var integer
     */
    const ERR_ARITHMETIC = 0x0012;

    /**
     * 除数为0
     *
     * @var integer
     */
    const ERR_DIVISIONBYZERO = 0x0013;

    /**
     * 类型错误
     *
     * @var integer
     */
    const ERR_TYPE = 0x0014;

    /**
     * 运行时异常
     *
     * @var integer
     */
    const EX_RUNTIME = 0x0031;

    /**
     * 错误的参数
     *
     * @var integer
     */
    const EX_INVALID_ARGS = 0x0032;

    /**
     * 预期之外的结果/值
     *
     * @var integer
     */
    const EX_UNEXPECTED_VALUE = 0x0033;

    /**
     * 上溢
     *
     * @var integer
     */
    const EX_UNDERFLOW = 0x0034;

    /**
     * 下溢
     *
     * @var integer
     */
    const EX_OVERFLOW = 0x0035;

    /**
     * 逻辑错误
     *
     * @var integer
     */
    const EX_LOGIC = 0x0036;

    /**
     * 配置错误
     *
     * @var integer
     */
    const EX_CONFIG = 0x0070;

    /**
     * 获取错误码对应的错误消息，如果不存在，返回错误码
     *
     * @param integer $code
     *            错误码
     * @param string|int $default
     *            为null，则返回错误码值
     * @return mixed
     *
     */
    public static function msg($code, $default = null)
    {
        $map = self::getExMap();
        if (isset($map[$code])) {
            return $map[$code];
        }
        return $default === null ? $code : $default;
    }

    /**
     * 错误码映射
     *
     * @return array
     */
    public static function getExMap()
    {
        static $map = [
            self::EX => '系统发生了异常',
            self::ERR => '发生了错误',
            self::ERR_ARITHMETIC => '算术错误',
            self::ERR_DIVISIONBYZERO => '除数为0',
            self::ERR_PARSE => '解析错误',
            self::ERR_TYPE => '类型错误',
            self::EX_RUNTIME => '运行时异常',
            self::EX_INVALID_ARGS => '错误的参数',
            self::EX_UNEXPECTED_VALUE => '预期之外的结果/值',
            self::EX_UNDERFLOW => '下溢异常',
            self::EX_OVERFLOW => '上溢异常',
            self::EX_LOGIC => '逻辑异常',
            self::EX_CONFIG => '配置错误'
        ];
        
        return $map;
    }
}