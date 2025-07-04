<?php

namespace NetBull\MediaBundle\Metadata;

class AmazonMetadataBuilder implements MetadataBuilderInterface
{
    /**
     * @var array
     */
    protected array $acl = [
        'private' => 'private',
        'public' => 'public-read',
        'open' => 'public-read-write',
        'auth_read' => 'authenticated-read',
        'owner_read' => 'bucket-owner-read',
        'owner_full_control' => 'bucket-owner-full-control',
    ];

    /**
     * Map of the extension-to-mime-types that we support.
     */
    public static array $mime_types = array(
        '3gp' => 'video/3gpp',
        'ai' => 'application/postscript',
        'aif' => 'audio/x-aiff',
        'aifc' => 'audio/x-aiff',
        'aiff' => 'audio/x-aiff',
        'asc' => 'text/plain',
        'atom' => 'application/atom+xml',
        'au' => 'audio/basic',
        'avi' => 'video/x-msvideo',
        'bcpio' => 'application/x-bcpio',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'cdf' => 'application/x-netcdf',
        'cgm' => 'image/cgm',
        'class' => 'application/octet-stream',
        'cpio' => 'application/x-cpio',
        'cpt' => 'application/mac-compactpro',
        'csh' => 'application/x-csh',
        'css' => 'text/css',
        'dcr' => 'application/x-director',
        'dif' => 'video/x-dv',
        'dir' => 'application/x-director',
        'djv' => 'image/vnd.djvu',
        'djvu' => 'image/vnd.djvu',
        'dll' => 'application/octet-stream',
        'dmg' => 'application/octet-stream',
        'dms' => 'application/octet-stream',
        'doc' => 'application/msword',
        'dtd' => 'application/xml-dtd',
        'dv' => 'video/x-dv',
        'dvi' => 'application/x-dvi',
        'dxr' => 'application/x-director',
        'eps' => 'application/postscript',
        'etx' => 'text/x-setext',
        'exe' => 'application/octet-stream',
        'ez' => 'application/andrew-inset',
        'flv' => 'video/x-flv',
        'gif' => 'image/gif',
        'gram' => 'application/srgs',
        'grxml' => 'application/srgs+xml',
        'gtar' => 'application/x-gtar',
        'gz' => 'application/x-gzip',
        'hdf' => 'application/x-hdf',
        'hqx' => 'application/mac-binhex40',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ice' => 'x-conference/x-cooltalk',
        'ico' => 'image/x-icon',
        'ics' => 'text/calendar',
        'ief' => 'image/ief',
        'ifb' => 'text/calendar',
        'iges' => 'model/iges',
        'igs' => 'model/iges',
        'jnlp' => 'application/x-java-jnlp-file',
        'jp2' => 'image/jp2',
        'jpe' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'js' => 'application/x-javascript',
        'kar' => 'audio/midi',
        'latex' => 'application/x-latex',
        'lha' => 'application/octet-stream',
        'lzh' => 'application/octet-stream',
        'm3u' => 'audio/x-mpegurl',
        'm4a' => 'audio/mp4a-latm',
        'm4p' => 'audio/mp4a-latm',
        'm4u' => 'video/vnd.mpegurl',
        'm4v' => 'video/x-m4v',
        'mac' => 'image/x-macpaint',
        'man' => 'application/x-troff-man',
        'mathml' => 'application/mathml+xml',
        'me' => 'application/x-troff-me',
        'mesh' => 'model/mesh',
        'mid' => 'audio/midi',
        'midi' => 'audio/midi',
        'mif' => 'application/vnd.mif',
        'mov' => 'video/quicktime',
        'movie' => 'video/x-sgi-movie',
        'mp2' => 'audio/mpeg',
        'mp3' => 'audio/mpeg',
        'mp4' => 'video/mp4',
        'mpe' => 'video/mpeg',
        'mpeg' => 'video/mpeg',
        'mpg' => 'video/mpeg',
        'mpga' => 'audio/mpeg',
        'ms' => 'application/x-troff-ms',
        'msh' => 'model/mesh',
        'mxu' => 'video/vnd.mpegurl',
        'nc' => 'application/x-netcdf',
        'oda' => 'application/oda',
        'ogg' => 'application/ogg',
        'ogv' => 'video/ogv',
        'pbm' => 'image/x-portable-bitmap',
        'pct' => 'image/pict',
        'pdb' => 'chemical/x-pdb',
        'pdf' => 'application/pdf',
        'pgm' => 'image/x-portable-graymap',
        'pgn' => 'application/x-chess-pgn',
        'pic' => 'image/pict',
        'pict' => 'image/pict',
        'png' => 'image/png',
        'pnm' => 'image/x-portable-anymap',
        'pnt' => 'image/x-macpaint',
        'pntg' => 'image/x-macpaint',
        'ppm' => 'image/x-portable-pixmap',
        'ppt' => 'application/vnd.ms-powerpoint',
        'ps' => 'application/postscript',
        'qt' => 'video/quicktime',
        'qti' => 'image/x-quicktime',
        'qtif' => 'image/x-quicktime',
        'ra' => 'audio/x-pn-realaudio',
        'ram' => 'audio/x-pn-realaudio',
        'ras' => 'image/x-cmu-raster',
        'rdf' => 'application/rdf+xml',
        'rgb' => 'image/x-rgb',
        'rm' => 'application/vnd.rn-realmedia',
        'roff' => 'application/x-troff',
        'rtf' => 'text/rtf',
        'rtx' => 'text/richtext',
        'sgm' => 'text/sgml',
        'sgml' => 'text/sgml',
        'sh' => 'application/x-sh',
        'shar' => 'application/x-shar',
        'silo' => 'model/mesh',
        'sit' => 'application/x-stuffit',
        'skd' => 'application/x-koan',
        'skm' => 'application/x-koan',
        'skp' => 'application/x-koan',
        'skt' => 'application/x-koan',
        'smi' => 'application/smil',
        'smil' => 'application/smil',
        'snd' => 'audio/basic',
        'so' => 'application/octet-stream',
        'spl' => 'application/x-futuresplash',
        'src' => 'application/x-wais-source',
        'sv4cpio' => 'application/x-sv4cpio',
        'sv4crc' => 'application/x-sv4crc',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        't' => 'application/x-troff',
        'tar' => 'application/x-tar',
        'tcl' => 'application/x-tcl',
        'tex' => 'application/x-tex',
        'texi' => 'application/x-texinfo',
        'texinfo' => 'application/x-texinfo',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'tr' => 'application/x-troff',
        'tsv' => 'text/tab-separated-values',
        'txt' => 'text/plain',
        'ustar' => 'application/x-ustar',
        'vcd' => 'application/x-cdlink',
        'vrml' => 'model/vrml',
        'vxml' => 'application/voicexml+xml',
        'wav' => 'audio/x-wav',
        'wbmp' => 'image/vnd.wap.wbmp',
        'wbxml' => 'application/vnd.wap.wbxml',
        'webm' => 'video/webm',
        'wml' => 'text/vnd.wap.wml',
        'wmlc' => 'application/vnd.wap.wmlc',
        'wmls' => 'text/vnd.wap.wmlscript',
        'wmlsc' => 'application/vnd.wap.wmlscriptc',
        'wmv' => 'video/x-ms-wmv',
        'wrl' => 'model/vrml',
        'xbm' => 'image/x-xbitmap',
        'xht' => 'application/xhtml+xml',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xml' => 'application/xml',
        'xpm' => 'image/x-xpixmap',
        'xsl' => 'application/xml',
        'xslt' => 'application/xslt+xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'xwd' => 'image/x-xwindowdump',
        'xyz' => 'chemical/x-xyz',
        'zip' => 'application/zip',
    );

    /**
     * @param array $settings
     */
    public function __construct(protected array $settings = [])
    {
    }

    /**
     * @return array
     */
    protected function getDefaultMetadata(): array
    {
        //merge acl
        $output = [];
        if (isset($this->settings['acl']) && !empty($this->settings['acl'])) {
            if(in_array($this->settings['acl'], $this->acl)){
                $output['ACL'] = $this->settings['acl'];
            }else{
                $output['ACL'] = $this->acl[$this->settings['acl']];
            }
        }

        //merge storage
        if (isset($this->settings['storage'])) {
            if ($this->settings['storage'] == 'standard') {
                $output['StorageClass'] = 'STANDARD';
            } elseif ($this->settings['storage'] == 'reduced') {
                $output['StorageClass'] = 'REDUCED_REDUNDANCY';
            }
        }

        //merge meta
        if (isset($this->settings['meta']) && !empty($this->settings['meta'])) {
            $output['Metadata'] = $this->settings['meta'];
        }

        //merge cache control header
        if (isset($this->settings['cache_control']) && !empty($this->settings['cache_control'])) {
            $output['CacheControl'] = $this->settings['cache_control'];
        }

        return $output;
    }

    /**
     * @param string $filename
     * @return string[]
     */
    protected function getContentType(string $filename): array
    {
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $contentType = self::getMimeType($extension);

        return ['contentType' => $contentType];
    }

    /**
     * @param string $filename
     * @return array
     */
    public function get(string $filename): array
    {
        return array_replace_recursive(
            $this->getDefaultMetadata(),
            $this->getContentType($filename)
        );
    }

    /**
     * Attempt to match the file extension to a known mime-type.
     *
     * @param string $ext (Required) The file extension to attempt to map.
     * @return string The mime-type to use for the file extension.
     */
    public static function getMimeType(string $ext): string
    {
        $ext = strtolower($ext);  // Make sure the passed in extension is lowercase
        return self::$mime_types[$ext] ?? 'application/octet-stream';
    }
}
