<?php
/**
 * Created by PhpStorm.
 * User: Charles
 * Date: 2019/04/01
 * Time: 19:50
 */

namespace app\index\service;

/**
 * ffmpeg助手类
 * Class FFMpegHelper
 * @package app\index\service
 */
class FFMpegHelper
{
    private $ffmpegBinPath = null;

    private $mediaFilePath = null;

    public function __construct()
    {
        if (!function_exists('shell_exec')) {
            exit('php.ini文件中disable_function移除shell_exec函数');
        }
        $path = env('FFMPEG_PATH');
        if (!is_file($path)) {
            exit('请在.env文件指定ffmpeg可执行文件路径');
        }
        $this->ffmpegBinPath = $path;
    }

    public function setPath($filePath)
    {
        if (!is_file($filePath)) {
            throw new \Exception('setPath 文件不可读');
        }
        $this->mediaFilePath = $filePath;
    }

    /**
     * 媒体信息，判断是否为视频，看$ret里面是否有‘seconds’的key
     * @return array
     * @throws \Exception
     */
    public function getInfo(): array
    {
        if (null === $this->mediaFilePath) {
            throw new \Exception('尚未设置文件');
        }
        $command = sprintf($this->ffmpegBinPath . ' -i "%s" 2>&1', $this->mediaFilePath);
        $commandResult = shell_exec($command);
        if (!strlen($commandResult)) {
            throw new \Exception('分析文件失败');
        }
        if (preg_match("/Duration: (.*?), start: (.*?), bitrate: (\d*) kb\/s/", $commandResult, $matches)) {
            $ret['duration'] = $matches[1]; // 视频长度
            $duration = explode(':', $matches[1]);
            $ret['seconds'] = $duration[0] * 3600 + $duration[1] * 60 + $duration[2]; // 转为秒数
            $ret['start'] = $matches[2]; // 开始时间
            $ret['bitrate'] = $matches[3]; // bitrate 码率 单位kb
        }
        // Stream #0:0(und): Video: h264 (High) (avc1 / 0x31637661), yuvj420p(pc, bt709), 544x960, 1163 kb/s, 30 fps, 30 tbr, 600 tbn, 1200 tbc (default)
        if (preg_match("/Video: (.*?), (.*?), (.*?), (.*?)[,\s]/", $commandResult, $matches)) {
            $ret['resolution'] = '';
            $ret['width'] = 0;
            $ret['height'] = 0;
            foreach ($matches as $match) {
                if (false !== strpos($match, 'x')) {
                    $items = explode(', ', $match);
                    foreach ($items as $item) {
                        if (false === strpos($item, 'x')) {
                            continue;
                        }
                        if (false !== strpos($item, 'Video:')) {
                            continue;
                        }
                        $ret['resolution'] = $item;
                        $tmp = explode(' [', $item);
                        $item = $tmp[0];
                        list($ret['width'], $ret['height']) = explode('x', $item);
                    }
                }
            }
        }
        // Stream #0:0: Audio: cook (cook / 0x6B6F6F63), 22050 Hz, stereo, fltp, 32 kb/s
        if (preg_match("/Audio: (.*), (\d*) Hz/", $commandResult, $matches)) {
            $ret['acodec'] = $matches[1];  // 音频编码
            $ret['asamplerate'] = $matches[2]; // 音频采样频率
        }

        if (isset($ret['seconds']) && isset($ret['start'])) {
            $ret['play_time'] = $ret['seconds'] + $ret['start']; // 实际播放时间
        }

        $ret['size'] = filesize($this->mediaFilePath); // 视频文件大小
        $ret['info'] = $commandResult;
        return $ret;
    }
}