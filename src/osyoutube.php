<?php
/**
 * @package   OSYouTube
 * @contact   www.alledia.com, hello@alledia.com
 * @copyright 2014 Alledia.com, All rights reserved
 * @license   http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

use Alledia\Framework\Joomla\Extension\AbstractPlugin;

defined('_JEXEC') or die();

require_once 'include.php';

if (defined('ALLEDIA_FRAMEWORK_LOADED')) {
    /**
     * OSYouTube Content Plugin
     *
     */
    class PlgContentOSYoutube extends AbstractPlugin
    {
        public function __construct(&$subject, $config = array())
        {
            $this->namespace = 'OSYouTube';

            parent::__construct($subject, $config);
        }

        /**
         * @param string $context
         * @param object $article
         * @param object $params
         * @param int    $page
         *
         * @return bool
         */
        public function onContentPrepare($context, &$article, &$params, $page = 0)
        {
            if (JString::strpos($article->text, '://www.youtube.com/watch') === false) {
                return true;
            }

            $this->init();

            // Note! The order of these expressions matters
            $regex = array(
                '#(?:<a.*?href=["\'](?:https?://(?:www\.)?youtube.com/watch\?v=([^\'"\#]+)(\#[^\'"\#]*)?[\'"][^>]*>(.+)?(?:</a>)))#',
                '#https?://(?:www\.)?youtube.com/watch\?v=([a-zA-Z0-9-_&;=]+)(\#[a-zA-Z0-9-_&;=]*)?#'
            );

            foreach ($regex as $r) {
                if (preg_match_all($r, $article->text, $matches)) {
                    foreach ($matches[0] as $k => $source) {
                        $urlHash = @$matches[2][$k];
                        $article->text = str_replace(
                            $source,
                            $this->youtubeCodeEmbed($matches[1][$k], $urlHash),
                            $article->text
                        );
                    }
                }
            }

            return true;
        }

        protected function youtubeCodeEmbed($videoCode, $urlHash = null)
        {
            $output = '';
            $params = $this->params;

            $width      = $params->get('width', 425);
            $height     = $params->get('height', 344);
            $responsive = $params->get('responsive', 1);

            if ($responsive) {
                $doc = JFactory::getDocument();
                $doc->addStyleSheet(JURI::base() . "plugins/content/osyoutube/style.css");
                $output .= '<div class="video-responsive">';
            }

            $query = explode('&', htmlspecialchars_decode($videoCode));
            $videoCode = array_shift($query);

            $attribs = array(
                'width'       => $width,
                'height'      => $height,
                'frameborder' => '0'
            );

            if ($this->isPro()) {
                $attribs['src'] = Alledia\OSYouTube\Pro\Embed::getUrl($params, $videoCode, $query, $urlHash);
            } else {
                $attribs['src'] = Alledia\OSYouTube\Free\Embed::getUrl($params, $videoCode, $query, $urlHash);
            }

            $output .= '<iframe ' . JArrayHelper::toString($attribs) . ' allowfullscreen></iframe>';

            if ($responsive) {
                $output .= '</div>';
            }

            return $output;
        }
    }
}
