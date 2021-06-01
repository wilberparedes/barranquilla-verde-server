<?php
/**
 * File: ModifiedImage.php
 * Author: Simon Jarvis
 * Copyright: Simon Jarvis
 * Date: Aug-11-06
 * Original link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
 *
 * Last Modified Date: Aug-04-11
 * Modified by: abimaelrc - http://www.forosdelweb.com/miembros/abimaelrc/
 * Modified by: iviamontes - http://www.forosdelweb.com/miembros/iviamontes/
 * Modified by: Triby - http://www.forosdelweb.com/miembros/triby/
 * Gif transparency by iviamontes
 * Watermark by abimaelrc/iviamontes
 * ResizeToFit by Triby
 */
 
class ModifiedImage{
    private $_image;
    private $_imageType;
    private $_transparent;
 
    /**
     * Original link: http://www.php.net/manual/es/function.imagecopymerge.php#92787
     * PNG ALPHA CHANNEL SUPPORT for imagecopymerge();
     * by Sina Salek
     * 
     * Bugfix by Ralph Voigt (bug which causes it
     * to work only for $src_x = $src_y = 0.
     * Also, inverting opacity is not necessary.)
     * 08-JAN-2011 
     **/ 
    private function _imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct){
        $cut = imagecreatetruecolor($src_w, $src_h);
        imagecopy($cut, $dst_im, 0, 0, $dst_x, $dst_y, $src_w, $src_h);
        imagecopy($cut, $src_im, 0, 0, $src_x, $src_y, $src_w, $src_h);
        imagecopymerge($dst_im, $cut, $dst_x, $dst_y, 0, 0, $src_w, $src_h, $pct);
    }
 
    private function _setPositionWatermark($width, $height, $position = 'bottom right', $paddingH = 10, $paddingV = 10)
    {
        switch(strtolower($position)){
            case 'top left':
                $h = $paddingH;
                $v = $paddingV;
                break;
            case 'top center':
                $h = ($this->getWidth() / 2) - ($width / 2) - $paddingH;
                $v = $paddingV;
                break;
            case 'top right':
                $h = $this->getWidth() - $width - $paddingH;
                $v = $paddingV;
                break;
            case 'middle left':
                $h = $paddingH;
                $v = ($this->getHeight() / 2) - ($height / 2) - $paddingV;
                break;
            case 'middle center':
                $h = ($this->getWidth() / 2) - ($width / 2) - $paddingH;
                $v = ($this->getHeight() / 2) - ($height / 2) - $paddingV;
                break;
            case 'middle right':
                $h = $this->getWidth() - $width - $paddingH;
                $v = ($this->getHeight() / 2) - ($height / 2) - $paddingV;
                break;
            case 'bottom left':
                $h = $paddingH;
                $v = $this->getHeight() - $height - $paddingV;
                break;
            case 'bottom center':
                $h = ($this->getWidth() / 2) - ($width / 2) - $paddingH;
                $v = $this->getHeight() - $height - $paddingV;
                break;
            default:
                $h = $this->getWidth() - $width - $paddingH;
                $v = $this->getHeight() - $height - $paddingV;
        }
        return array('horizontal'=>$h, 'vertical'=>$v);
    }
 
    public function __construct($fileName=null, $transparent=false)
    {
        $this->setTransparent($transparent);
 
        if(!is_null($fileName)){
            $this->load($fileName);
        }
    }
 
    public function setTransparent($bool)
    {
        $this->_transparent = (boolean)$bool;
    }
 
    public function load($fileName)
    {
        $imageInfo = getimagesize($fileName);
        $this->_imageType = $imageInfo[2];
 
        if($this->_imageType == IMAGETYPE_JPEG){
            $this->_image = imagecreatefromjpeg($fileName);
        }
        elseif($this->_imageType == IMAGETYPE_GIF){
            $this->_image = imagecreatefromgif($fileName);
        }
        elseif($this->_imageType == IMAGETYPE_PNG){
            $this->_image = imagecreatefrompng($fileName);
        }
    }
 
    public function save($fileName, $compression = 75, $permissions = null)
    {
        if($this->_imageType == IMAGETYPE_JPEG){
            imagejpeg($this->_image, $fileName, $compression);
        }
        elseif($this->_imageType == IMAGETYPE_GIF){
            imagegif($this->_image, $fileName);
        }
        elseif($this->_imageType == IMAGETYPE_PNG){
            imagepng($this->_image, $fileName);
        }
 
        if(!is_null($permissions)) {
            chmod($fileName, $permissions);
        }
    }
 
    public function output()
    {
        if($this->_imageType == IMAGETYPE_JPEG){
            imagejpeg($this->_image);
        }
        elseif($this->_imageType == IMAGETYPE_GIF){
            imagegif($this->_image);
        }
        elseif($this->_imageType == IMAGETYPE_PNG){
            imagepng($this->_image);
        }   
    }
 
    public function getWidth()
    {
        return imagesx($this->_image);
    }
 
    public function getHeight()
    {
        return imagesy($this->_image);
    }
 
    public function resizeToHeight($height)
    {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width,$height);
    }
 
    public function resizeToWidth($width)
    {
        $ratio = $width / $this->getWidth();
        $height = $this->getHeight() * $ratio;
        $this->resize($width, $height);
    }
 
    public function scale($scale)
    {
        $width = $this->getWidth() * $scale / 100;
        $height = $this->getHeight() * $scale / 100;
        $this->resize($width, $height);
    }
 
    public function resize($width, $height)
    {
        $newImage = imagecreatetruecolor($width, $height);
        if($this->_imageType == IMAGETYPE_PNG && $this->_transparent === true){
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
            imagefilledrectangle($newImage, 0, 0, $width, $height, imagecolorallocatealpha($newImage, 255, 255, 255, 127));
        }
        elseif($this->_imageType == IMAGETYPE_GIF && $this->_transparent === true){
            $index = imagecolortransparent($this->_image);
            if($index != -1 && $index != 255){
                $colors = imagecolorsforindex($this->_image, $index);
                $transparent = imagecolorallocatealpha($newImage, $colors['red'], $colors['green'], $colors['blue'], $colors['alpha']);
                imagefill($newImage, 0, 0, $transparent);
                imagecolortransparent($newImage, $transparent);
            }
        }
        imagecopyresampled($newImage, $this->_image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->_image = $newImage;
    }
 
    public function resizeToFit($width, $height, $margins = false, $hexBckColor = '000000') {
        $ratioW = $width / $this->getWidth();
        $ratioH = $height / $this->getHeight();
        $ratio = ($margins === false) ? max($ratioW, $ratioH) : min($ratioW, $ratioH);
        $newW = floor($this->getWidth() * $ratio);
        $newH = floor($this->getHeight() * $ratio);
 
        $this->resize($newW, $newH);
 
        if($newW != $width || $newH != $height) {
            $newImage = imagecreatetruecolor($width, $height);
            imagefill($newImage, 0, 0, "0x$hexBckColor");
 
            $ox = ($newW > $width) ? floor(($newW - $width) / 2) : 0;
            $oy = ($newH > $height) ? floor(($newH - $width) / 2) : 0;
            $dx = ($newW < $width) ? floor(($width - $newW) / 2) : 0;
            $dy = ($newH < $height) ? floor(($height - $newH) / 2) : 0; 
 
            imagecopy($newImage, $this->_image, $dx, $dy, $ox, $oy, $newW, $newH);
            $this->_image = $newImage;
        }
    }
 
    public function imgWatermark($img, $opacity = 100, $position = 'bottom right', $paddingH = 10, $paddingV = 10)
    {
        $iw = getimagesize($img);
        $width = $iw[0];
        $height = $iw[1];
 
        $p = $this->_setPositionWatermark($width, $height, $position, $paddingH, $paddingV);
 
        imagealphablending($this->_image, true);
        $watermark = imagecreatefrompng($img);
        $this->_imagecopymerge_alpha($this->_image, $watermark, $p['horizontal'], $p['vertical'], 0, 0, $width, $height, $opacity);
        imagedestroy($watermark);
 
        return $this->_image;
    }
 
    public function stringWatermark($string, $opacity = 100, $color = '000000', $position = 'bottom right', $paddingH = 10, $paddingV = 10){
        $width = imagefontwidth(5) * strlen($string);
        $height = imagefontwidth(5) + 10;
 
        $p = $this->_setPositionWatermark($width, $height, $position, $paddingH, $paddingV);
 
        $watermark = imagecreatetruecolor($width, $height);
        imagealphablending($watermark, false);
        imagesavealpha($watermark, true);
        imagefilledrectangle($watermark, 0, 0, $width, $height, imagecolorallocatealpha($watermark, 255, 255, 255, 127));
 
        imagestring($watermark, 5, 0, 0, $string, "0x$color");
        $this->_imagecopymerge_alpha($this->_image, $watermark, $p['horizontal'], $p['vertical'], 0, 0, $width, $height, $opacity);
 
        return $this->_image;
    }
}
 
 
?>