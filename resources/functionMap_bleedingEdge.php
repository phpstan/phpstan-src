<?php // phpcs:ignoreFile

return [
	'new' => [
		'error_log' => ['bool', 'message'=>'string', 'message_type='=>'0|1|2|3|4', 'destination='=>'string', 'extra_headers='=>'string'],
		'Imagick::adaptiveBlurImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::adaptiveSharpenImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::addNoiseImage' => ['bool', 'noise_type'=>'Imagick::NOISE_*', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::autoGammaImage' => ['bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::autoLevelImage' => ['bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::blurImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::brightnessContrastImage' => ['bool', 'brightness'=>'float', 'contrast'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::clampImage' => ['bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::combineImages' => ['Imagick', 'channeltype'=>'Imagick::CHANNEL_*'],
		'Imagick::compareImageChannels' => ['array{Imagick,float}', 'image'=>'imagick', 'channeltype'=>'Imagick::CHANNEL_*', 'metrictype'=>'Imagick::METRIC_*'],
		'Imagick::compareImageLayers' => ['Imagick', 'method'=>'Imagick::LAYERMETHOD_*'],
		'Imagick::compareImages' => ['array{Imagick,float}', 'compare'=>'imagick', 'metric'=>'Imagick::METRIC_*'],
		'Imagick::compositeImage' => ['bool', 'composite_object'=>'imagick', 'composite'=>'Imagick::COMPOSITE_*', 'x'=>'int', 'y'=>'int', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::contrastStretchImage' => ['bool', 'black_point'=>'float', 'white_point'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::convolveImage' => ['bool', 'kernel'=>'array', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::distortImage' => ['bool', 'method'=>'Imagick::DISTORTION_*', 'arguments'=>'array', 'bestfit'=>'bool'],
		'Imagick::evaluateImage' => ['bool', 'op'=>'Imagick::EVALUATE_*', 'constant'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::exportImagePixels' => ['list<int>', 'x'=>'int', 'y'=>'int', 'width'=>'int', 'height'=>'int', 'map'=>'string', 'storage'=>'Imagick::PIXEL_*'],
		'Imagick::floodFillPaintImage' => ['bool', 'fill'=>'mixed', 'fuzz'=>'float', 'target'=>'mixed', 'x'=>'int', 'y'=>'int', 'invert'=>'bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::functionImage' => ['bool', 'function'=>'Imagick::FUNCTION_*', 'arguments'=>'array', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::fxImage' => ['Imagick', 'expression'=>'string', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::gammaImage' => ['bool', 'gamma'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::gaussianBlurImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::getImageChannelDepth' => ['int', 'channel'=>'Imagick::CHANNEL_*'],
		'Imagick::getImageChannelDistortion' => ['float', 'reference'=>'imagick', 'channel'=>'Imagick::CHANNEL_*', 'metric'=>'Imagick::METRIC_*'],
		'Imagick::getImageChannelDistortions' => ['float', 'reference'=>'imagick', 'metric'=>'Imagick::METRIC_*', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::getImageChannelExtrema' => ['array{minima:0|positive-int,maxima:0|positive-int}', 'channel'=>'Imagick::CHANNEL_*'],
		'Imagick::getImageChannelKurtosis' => ['array{kurtosis:float,skewness:float}', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::getImageChannelMean' => ['array{mean:float,standardDeviation:float}', 'channel'=>'Imagick::CHANNEL_*'],
		'Imagick::getImageChannelRange' => ['array{minima:float,maxima:float}', 'channel'=>'Imagick::CHANNEL_*'],
		'Imagick::getImageDistortion' => ['float', 'reference'=>'magickwand', 'metric'=>'Imagick::METRIC_*'],
		'Imagick::getResource' => ['int', 'type'=>'Imagick::RESOURCETYPE_*'],
		'Imagick::getResourceLimit' => ['int', 'type'=>'Imagick::RESOURCETYPE_*'],
		'Imagick::importImagePixels' => ['bool', 'x'=>'int', 'y'=>'int', 'width'=>'int', 'height'=>'int', 'map'=>'string', 'storage'=>'Imagick::PIXEL_*', 'pixels'=>'array'],
		'Imagick::levelImage' => ['bool', 'blackpoint'=>'float', 'gamma'=>'float', 'whitepoint'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::mergeImageLayers' => ['Imagick', 'layer_method'=>'Imagick::LAYERMETHOD_*'],
		'Imagick::montageImage' => ['Imagick', 'draw'=>'imagickdraw', 'tile_geometry'=>'string', 'thumbnail_geometry'=>'string', 'mode'=>'Imagick::MONTAGEMODE_*', 'frame'=>'string'],
		'Imagick::morphology' => ['bool', 'morphologyMethod'=>'Imagick::MORPHOLOGY_*', 'iterations'=>'int', 'ImagickKernel'=>'ImagickKernel', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::motionBlurImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'angle'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::negateImage' => ['bool', 'gray'=>'bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::normalizeImage' => ['bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::opaquePaintImage' => ['bool', 'target'=>'mixed', 'fill'=>'mixed', 'fuzz'=>'float', 'invert'=>'bool', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::orderedPosterizeImage' => ['bool', 'threshold_map'=>'string', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::paintFloodfillImage' => ['bool', 'fill'=>'mixed', 'fuzz'=>'float', 'bordercolor'=>'mixed', 'x'=>'int', 'y'=>'int', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::paintOpaqueImage' => ['bool', 'target'=>'mixed', 'fill'=>'mixed', 'fuzz'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::radialBlurImage' => ['bool', 'angle'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::randomThresholdImage' => ['bool', 'low'=>'float', 'high'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::remapImage' => ['bool', 'replacement'=>'imagick', 'dither'=>'Imagick::DITHERMETHOD_*'],
		'Imagick::rotationalBlurImage' => ['bool', 'float'=>'string', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::segmentImage' => ['bool', 'colorspace'=>'Imagick::COLORSPACE_*', 'cluster_threshold'=>'float', 'smooth_threshold'=>'float', 'verbose='=>'bool'],
		'Imagick::selectiveBlurImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'threshold'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::separateImageChannel' => ['bool', 'channel'=>'Imagick::CHANNEL_*'],
		'Imagick::setColorspace' => ['bool', 'colorspace'=>'Imagick::COLORSPACE_*'],
		'Imagick::setCompression' => ['bool', 'compression'=>'Imagick::COMPRESSION_*'],
		'Imagick::setGravity' => ['bool', 'gravity'=>'Imagick::GRAVITY_*'],
		'Imagick::setImageAlphaChannel' => ['bool', 'mode'=>'Imagick::ALPHACHANNEL_*'],
		'Imagick::setImageChannelDepth' => ['bool', 'channel'=>'Imagick::CHANNEL_*', 'depth'=>'int'],
		'Imagick::setImageChannelMask' => ['', 'channel'=>'Imagick::CHANNEL_*'],
		'Imagick::setImageClipMask' => ['bool', 'clip_mask'=>'imagick'],
		'Imagick::setImageColormapColor' => ['bool', 'index'=>'int', 'color'=>'imagickpixel'],
		'Imagick::setImageColorspace' => ['bool', 'colorspace'=>'Imagick::COLORSPACE_*'],
		'Imagick::setImageCompose' => ['bool', 'compose'=>'Imagick::COMPOSITE_*'],
		'Imagick::setImageCompression' => ['bool', 'compression'=>'Imagick::COMPRESSION_*'],
		'Imagick::setImageDispose' => ['bool', 'dispose'=>'Imagick::DISPOSE_*'],
		'Imagick::setImageGravity' => ['bool', 'gravity'=>'Imagick::GRAVITY_*'],
		'Imagick::setImageInterlaceScheme' => ['bool', 'interlace_scheme'=>'Imagick::INTERLACE_*'],
		'Imagick::setImageInterpolateMethod' => ['bool', 'method'=>'Imagick::INTERPOLATE_*'],
		'Imagick::setImageOrientation' => ['bool', 'orientation'=>'Imagick::ORIENTATION_*'],
		'Imagick::setImageRenderingIntent' => ['bool', 'rendering_intent'=>'Imagick::RENDERINGINTENT_*'],
		'Imagick::setImageType' => ['bool', 'image_type'=>'Imagick::IMGTYPE_*'],
		'Imagick::setType' => ['bool', 'image_type'=>'Imagick::IMGTYPE_*'],
		'Imagick::sharpenImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::sigmoidalContrastImage' => ['bool', 'sharpen'=>'bool', 'alpha'=>'float', 'beta'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::similarityImage' => ['Imagick', 'imagick'=>'Imagick', '&bestMatch'=>'array', '&similarity'=>'float', 'similarity_threshold'=>'float', 'metric'=>'Imagick::METRIC_*'],
		'Imagick::sparseColorImage' => ['bool', 'sparse_method'=>'Imagick::SPARSECOLORMETHOD_*', 'arguments'=>'array', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::statisticImage' => ['bool', 'type'=>'Imagick::STATISTIC_*', 'width'=>'int', 'height'=>'int', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::thresholdImage' => ['bool', 'threshold'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'Imagick::transformImageColorspace' => ['bool', 'colorspace'=>'Imagick::COLORSPACE_*'],
		'Imagick::unsharpMaskImage' => ['bool', 'radius'=>'float', 'sigma'=>'float', 'amount'=>'float', 'threshold'=>'float', 'channel='=>'Imagick::CHANNEL_*'],
		'ImagickDraw::color' => ['bool', 'x'=>'float', 'y'=>'float', 'paintmethod'=>'Imagick::PAINT_*'],
		'ImagickDraw::composite' => ['bool', 'compose'=>'Imagick::COMPOSITE_*', 'x'=>'float', 'y'=>'float', 'width'=>'float', 'height'=>'float', 'compositewand'=>'imagick'],
		'ImagickDraw::getFillRule' => ['Imagick::FILLRULE_*'],
		'ImagickDraw::getFontStretch' => ['Imagick::STRETCH_*'],
		'ImagickDraw::getFontStyle' => ['Imagick::STYLE_*'],
		'ImagickDraw::getGravity' => ['Imagick::GRAVITY_*'],
		'ImagickDraw::getStrokeLineCap' => ['Imagick::LINECAP_*'],
		'ImagickDraw::getStrokeLineJoin' => ['Imagick::LINEJOIN_*'],
		'ImagickDraw::getTextAlignment' => ['Imagick::ALIGN_*'],
		'ImagickDraw::getTextDecoration' => ['Imagick::DECORATION_*'],
		'ImagickDraw::matte' => ['bool', 'x'=>'float', 'y'=>'float', 'paintmethod'=>'Imagick::PAINT_*'],
		'ImagickDraw::setClipRule' => ['bool', 'fill_rule'=>'Imagick::FILLRULE_*'],
		'ImagickDraw::setFillRule' => ['bool', 'fill_rule'=>'Imagick::FILLRULE_*'],
		'ImagickDraw::setFontStretch' => ['bool', 'fontstretch'=>'Imagick::STRETCH_*'],
		'ImagickDraw::setFontStyle' => ['bool', 'style'=>'Imagick::STYLE_*'],
		'ImagickDraw::setGravity' => ['bool', 'gravity'=>'Imagick::GRAVITY_*'],
		'ImagickDraw::setStrokeLineCap' => ['bool', 'linecap'=>'Imagick::LINECAP_*'],
		'ImagickDraw::setStrokeLineJoin' => ['bool', 'linejoin'=>'Imagick::LINEJOIN_*'],
		'ImagickDraw::setTextAlignment' => ['bool', 'alignment'=>'Imagick::ALIGN_*'],
		'ImagickDraw::setTextAntialias' => ['bool', 'antialias'=>'bool'],
		'ImagickDraw::setTextDecoration' => ['bool', 'decoration'=>'Imagick::DECORATION_*'],
		'ImagickKernel::fromBuiltin' => ['ImagickKernel', 'kernelType'=>'Imagick::KERNEL_*', 'kernelString'=>'string'],
		'ImagickKernel::scale' => ['void', 'scale'=>'float', 'normalizeFlag'=>'Imagick::NORMALIZE_KERNEL_*'],
		'max' => ['', '...arg1'=>'non-empty-array'],
		'min' => ['', '...arg1'=>'non-empty-array'],
		'file' => ['list<string>|false', 'filename'=>'string', 'flags='=>'int-mask<FILE_USE_INCLUDE_PATH|FILE_IGNORE_NEW_LINES|FILE_SKIP_EMPTY_LINES|FILE_NO_DEFAULT_CONTEXT>', 'context='=>'resource'],
		'flock' => ['bool', 'fp'=>'resource', 'operation'=>'int-mask<LOCK_SH, LOCK_EX, LOCK_UN, LOCK_NB>', '&w_wouldblock='=>'int'],
		'ftp_append' => ['bool', 'ftp'=>'resource', 'remote_file'=>'string', 'local_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY'],
		'ftp_fget' => ['bool', 'stream'=>'resource', 'fp'=>'resource', 'remote_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY', 'resumepos='=>'int'],
		'ftp_fput' => ['bool', 'stream'=>'resource', 'remote_file'=>'string', 'fp'=>'resource', 'mode='=>'FTP_ASCII|FTP_BINARY', 'startpos='=>'int'],
		'ftp_get' => ['bool', 'stream'=>'resource', 'local_file'=>'string', 'remote_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY', 'resume_pos='=>'int'],
		'ftp_nb_fget' => ['int', 'stream'=>'resource', 'fp'=>'resource', 'remote_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY', 'resumepos='=>'int'],
		'ftp_nb_fput' => ['int', 'stream'=>'resource', 'remote_file'=>'string', 'fp'=>'resource', 'mode='=>'FTP_ASCII|FTP_BINARY', 'startpos='=>'int'],
		'ftp_nb_get' => ['int|false', 'stream'=>'resource', 'local_file'=>'string', 'remote_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY', 'resume_pos='=>'int'],
		'ftp_nb_put' => ['int|false', 'stream'=>'resource', 'remote_file'=>'string', 'local_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY', 'startpos='=>'int'],
		'ftp_put' => ['bool', 'stream'=>'resource', 'remote_file'=>'string', 'local_file'=>'string', 'mode='=>'FTP_ASCII|FTP_BINARY', 'startpos='=>'int'],
		'scandir' => ['list<string>|false', 'dir'=>'string', 'sorting_order='=>'SCANDIR_SORT_ASCENDING|SCANDIR_SORT_DESCENDING| SCANDIR_SORT_NONE', 'context='=>'resource'],
		'stream_socket_client' => ['resource|false', 'remoteaddress'=>'string', '&w_errcode='=>'int', '&w_errstring='=>'string', 'timeout='=>'float', 'flags='=>'int-mask<STREAM_CLIENT_CONNECT|STREAM_CLIENT_ASYNC_CONNECT|STREAM_CLIENT_PERSISTENT>', 'context='=>'resource'],
		'extract' => ['int', '&rw_var_array'=>'array', 'extract_type='=>'EXTR_OVERWRITE|EXTR_SKIP|EXTR_PREFIX_SAME|EXTR_PREFIX_ALL|EXTR_PREFIX_INVALID|EXTR_IF_EXISTS|EXTR_PREFIX_IF_EXISTS|EXTR_REFS', 'prefix='=>'string|null'],
		'json_decode' => ['mixed', 'json'=>'string', 'assoc='=>'bool|null', 'depth='=>'positive-int', 'options='=>'int-mask<0|JSON_BIGINT_AS_STRING|JSON_INVALID_UTF8_IGNORE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_OBJECT_AS_ARRAY|JSON_THROW_ON_ERROR>'],
	],
	'old' => [

	]
];
