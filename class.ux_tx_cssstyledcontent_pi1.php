<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 2003 Martin Holtz (typo3@martinholtz.de)
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is 
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
* 
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
* 
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once (PATH_t3lib.'class.t3lib_div.php');
require_once(PATH_tslib.'class.tslib_pibase.php');
/** 
 * Plugin 'CSC With Multimedia'
 *
 * @author	Martin Holtz <typo3@martinholtz.de>
 */
class ux_tx_cssstyledcontent_pi1 extends tx_cssstyledcontent_pi1  {


	 function render_textpic($content, $conf)	{
		$renderMethod = $this->cObj->stdWrap($conf['renderMethod'], $conf['renderMethod.']);

			// Render using the default IMGTEXT code (table-based)
		if (!$renderMethod || $renderMethod == 'table')	{
			return $this->cObj->IMGTEXT($conf);
		}

			// Specific configuration for the chosen rendering method
		if (is_array($conf['rendering.'][$renderMethod . '.']))	{
			$conf = $this->cObj->joinTSarrays($conf, $conf['rendering.'][$renderMethod . '.']);
		}

			// Image or Text with Image?
		if (is_array($conf['text.']))	{
			$content = $this->cObj->stdWrap($this->cObj->cObjGet($conf['text.'], 'text.'), $conf['text.']);
		}

		$imgList = trim($this->cObj->stdWrap($conf['imgList'], $conf['imgList.']));

		if (!$imgList)	{
				// No images, that's easy
			if (is_array($conf['stdWrap.']))	{
				return $this->cObj->stdWrap($content, $conf['stdWrap.']);
			}
			return $content;
		}

		$imgs = t3lib_div::trimExplode(',', $imgList);
		$imgStart = intval($this->cObj->stdWrap($conf['imgStart'], $conf['imgStart.']));
		$imgCount = count($imgs) - $imgStart;
		$imgMax = intval($this->cObj->stdWrap($conf['imgMax'], $conf['imgMax.']));
		if ($imgMax)	{
			$imgCount = t3lib_div::intInRange($imgCount, 0, $conf['imgMax']);	// reduce the number of images.
		}

		$imgPath = $this->cObj->stdWrap($conf['imgPath'], $conf['imgPath.']);

			// Global caption
		$caption = '';
		if (!$conf['captionSplit'] && !$conf['imageTextSplit'] && is_array($conf['caption.']))	{
			$caption = $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
		}

			// Positioning
		$position = $this->cObj->stdWrap($conf['textPos'], $conf['textPos.']);

		$imagePosition = $position&7;	// 0,1,2 = center,right,left
		$contentPosition = $position&24;	// 0,8,16,24 (above,below,intext,intext-wrap)
		$align = $this->cObj->align[$imagePosition];
		$textMargin = intval($this->cObj->stdWrap($conf['textMargin'],$conf['textMargin.']));
		if (!$conf['textMargin_outOfText'] && $contentPosition < 16)	{
			$textMargin = 0;
		}

		$colspacing = intval($this->cObj->stdWrap($conf['colSpace'], $conf['colSpace.']));
		$rowspacing = intval($this->cObj->stdWrap($conf['rowSpace'], $conf['rowSpace.']));

		$border = intval($this->cObj->stdWrap($conf['border'], $conf['border.'])) ? 1:0;
		$borderColor = $this->cObj->stdWrap($conf['borderCol'], $conf['borderCol.']);
		$borderThickness = intval($this->cObj->stdWrap($conf['borderThick'], $conf['borderThick.']));

		$borderColor = $borderColor?$borderColor:'black';
		$borderThickness = $borderThickness?$borderThickness:1;
		$borderSpace = (($conf['borderSpace']&&$border) ? intval($conf['borderSpace']) : 0);

			// Generate cols
		$cols = intval($this->cObj->stdWrap($conf['cols'],$conf['cols.']));
		$colCount = ($cols > 1) ? $cols : 1;
		if ($colCount > $imgCount)	{$colCount = $imgCount;}
		$rowCount = ceil($imgCount / $colCount);

			// Generate rows
		$rows = intval($this->cObj->stdWrap($conf['rows'],$conf['rows.']));
		if ($rows>1)	{
			$rowCount = $rows;
			if ($rowCount > $imgCount)	{$rowCount = $imgCount;}
			$colCount = ($rowCount>1) ? ceil($imgCount / $rowCount) : $imgCount;
		}

			// Max Width
		$maxW = intval($this->cObj->stdWrap($conf['maxW'], $conf['maxW.']));

		if ($contentPosition>=16)	{	// in Text
			$maxWInText = intval($this->cObj->stdWrap($conf['maxWInText'],$conf['maxWInText.']));
			if (!$maxWInText)	{
					// If maxWInText is not set, it's calculated to the 50% of the max
				$maxW = round($maxW/100*50);
			} else {
				$maxW = $maxWInText;
			}
		}

			// All columns have the same width:
		$defaultColumnWidth = ceil(($maxW-$colspacing*($colCount-1)-$colCount*$border*($borderThickness+$borderSpace)*2)/$colCount);

			// Specify the maximum width for each column
		$columnWidths = array();
		$colRelations = trim($this->cObj->stdWrap($conf['colRelations'],$conf['colRelations.']));
		if (!$colRelations)	{
				// Default 1:1-proportion, all columns same width
			for ($a=0;$a<$colCount;$a++)	{
				$columnWidths[$a] = $defaultColumnWidth;
			}
		} else {
				// We need another proportion
			$rel_parts = explode(':',$colRelations);
			$rel_total = 0;
			for ($a=0;$a<$colCount;$a++)	{
				$rel_parts[$a] = intval($rel_parts[$a]);
				$rel_total+= $rel_parts[$a];
			}
			if ($rel_total)	{
				for ($a=0;$a<$colCount;$a++)	{
					$columnWidths[$a] = round(($defaultColumnWidth*$colCount)/$rel_total*$rel_parts[$a]);
				}
				if (min($columnWidths)<=0 || max($rel_parts)/min($rel_parts)>10)	{
					// The difference in size between the largest and smalles must be within a factor of ten.
					for ($a=0;$a<$colCount;$a++)	{
						$columnWidths[$a] = $defaultColumnWidth;
					}
				}
			}
		}
		$image_compression = intval($this->cObj->stdWrap($conf['image_compression'],$conf['image_compression.']));
		$image_effects = intval($this->cObj->stdWrap($conf['image_effects'],$conf['image_effects.']));
		$image_frames = intval($this->cObj->stdWrap($conf['image_frames.']['key'],$conf['image_frames.']['key.']));

			// EqualHeight
		$equalHeight = intval($this->cObj->stdWrap($conf['equalH'],$conf['equalH.']));
		if ($equalHeight)	{
				// Initiate gifbuilder object in order to get dimensions AND calculate the imageWidth's
			$gifCreator = t3lib_div::makeInstance('tslib_gifbuilder');
			$gifCreator->init();
			$relations_cols = Array();
			for ($a=0; $a<$imgCount; $a++)	{
				$imgKey = $a+$imgStart;
				$imgInfo = $gifCreator->getImageDimensions($imgPath.$imgs[$imgKey]);
				$rel = $imgInfo[1] / $equalHeight;	// relationship between the original height and the wished height
				if ($rel)	{	// if relations is zero, then the addition of this value is omitted as the image is not expected to display because of some error.
					$relations_cols[floor($a/$colCount)] += $imgInfo[0]/$rel;	// counts the total width of the row with the new height taken into consideration.
				}
			}
		}

			// Fetches pictures
		$splitArr = array();
		$splitArr['imgObjNum'] = $conf['imgObjNum'];
		$splitArr = $GLOBALS['TSFE']->tmpl->splitConfArray($splitArr, $imgCount);

		$imageRowsFinalWidths = Array();	// contains the width of every image row
		$imgsTag = array();
		$origImages = array();
		for ($a=0; $a<$imgCount; $a++)	{
			$imgKey = $a+$imgStart;
			$totalImagePath = $imgPath.$imgs[$imgKey];

			$GLOBALS['TSFE']->register['IMAGE_NUM'] = $a;
			$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $a;
			$GLOBALS['TSFE']->register['ORIG_FILENAME'] = $totalImagePath;

			$this->cObj->data[$this->cObj->currentValKey] = $totalImagePath;
			$imgObjNum = intval($splitArr[$a]['imgObjNum']);
			$imgConf = $conf[$imgObjNum.'.'];

			if ($equalHeight)	{
				$scale = 1;
				$totalMaxW = $defaultColumnWidth*$colCount;
				$rowTotalMaxW = $relations_cols[floor($a/$colCount)];
				if ($rowTotalMaxW > $totalMaxW)	{
					$scale = $rowTotalMaxW / $totalMaxW;
				}

					// transfer info to the imageObject. Please note, that
				$imgConf['file.']['height'] = round($equalHeight/$scale);

					// other stuff will be calculated accordingly:
				unset($imgConf['file.']['width']);
				unset($imgConf['file.']['maxW']);
				unset($imgConf['file.']['maxH']);
				unset($imgConf['file.']['minW']);
				unset($imgConf['file.']['minH']);
				unset($imgConf['file.']['width.']);
				unset($imgConf['file.']['maxW.']);
				unset($imgConf['file.']['maxH.']);
				unset($imgConf['file.']['minW.']);
				unset($imgConf['file.']['minH.']);
			} else {
				$imgConf['file.']['maxW'] = $columnWidths[($a%$colCount)];
			}

			$titleInLink = $this->cObj->stdWrap($imgConf['titleInLink'], $imgConf['titleInLink.']);
			$titleInLinkAndImg = $this->cObj->stdWrap($imgConf['titleInLinkAndImg'], $imgConf['titleInLinkAndImg.']);
			$oldATagParms = $GLOBALS['TSFE']->ATagParams;
			if ($titleInLink)	{
					// Title in A-tag instead of IMG-tag
				$titleText = trim($this->cObj->stdWrap($imgConf['titleText'], $imgConf['titleText.']));
				if ($titleText)	{
						// This will be used by the IMAGE call later:
					$GLOBALS['TSFE']->ATagParams .= ' title="'. $titleText .'"';
				}
			}
			if ($imgConf || $imgConf['file'])	{
				if ($this->cObj->image_effects[$image_effects])	{
					$imgConf['file.']['params'] .= ' '.$this->cObj->image_effects[$image_effects];
				}
				if ($image_frames)	{
					if (is_array($conf['image_frames.'][$image_frames.'.']))	{
						$imgConf['file.']['m.'] = $conf['image_frames.'][$image_frames.'.'];
					}
				}
				if ($image_compression && $imgConf['file'] != 'GIFBUILDER')	{
					if ($image_compression == 1)	{
						$tempImport = $imgConf['file.']['import'];
						$tempImport_dot = $imgConf['file.']['import.'];
						unset($imgConf['file.']);
						$imgConf['file.']['import'] = $tempImport;
						$imgConf['file.']['import.'] = $tempImport_dot;
					} elseif (isset($this->cObj->image_compression[$image_compression])) {
						$imgConf['file.']['params'] .= ' '.$this->cObj->image_compression[$image_compression]['params'];
						$imgConf['file.']['ext'] = $this->cObj->image_compression[$image_compression]['ext'];
						unset($imgConf['file.']['ext.']);
					}
				}
				if ($titleInLink && ! $titleInLinkAndImg)	{
						// Check if the image will be linked
					$link = $this->cObj->imageLinkWrap('', $totalImagePath, $imgConf['imageLinkWrap.']);
					if ($link)	{
							// Title in A-tag only (set above: ATagParams), not in IMG-tag
						unset($imgConf['titleText']);
						unset($imgConf['titleText.']);
						$imgConf['emptyTitleHandling'] = 'removeAttr';
					}
				}
// MH: Begin Changes for Multimedia in textpic
				$fileextension = array_pop(explode('.',$this->cObj->stdWrap('Text',array('data' => 'current:1'))));
				if (!isset($conf['multimedia'])) $conf['multimedia'] = 'swf';
				$multimedia = t3lib_div::trimExplode(',',$conf['multimedia'],true);
				// mutlimedia file?
				if (in_array(strtolower($fileextension),t3lib_div::trimExplode(',',$conf['multimedia'],true))) {
					$multimediaConf['file'] = $this->cObj->stdWrap('Text',array('data' => 'current:1'));
					$multimediaConf['params'] = isset($conf['multimedia.']['params'])?$conf['multimedia.']['params']:'';
					$height = '';
					if (isset($imgConf['file.']) && isset($imgConf['file.']['height'])) $height = $imgConf['file.']['height']; 
					if ('' != trim($height)) {
						$multimediaConf['params'] .= ' height='.$height.' ';
					} else {
						$multimediaConf['params'] .= ' height=100% ';
					}

					if (!isset($imgConf['file.']['width.'])) {
						$width = (isset($imgConf['file.']['maxW']))?$imgConf['file.']['maxW']:'';
					} elseif(!isset($imgConf['file.']['maxW'])) {
						$width = $this->cObj->stdWrap('?',$imgConf['file.']['width.']);
					} else {
						$width = min($imgConf['file.']['maxW'],$this->cObj->stdWrap('?',$imgConf['file.']['width.']));
					}
					if ('' != trim($width) && 0 < intval($width)) {
						$multimediaConf['params'] .= "\n".' width='.$width.' ';
					} else {
						$multimediaConf['params'] .= "\n".' width=100% ';
					}
					$imgsTag[$imgKey] = $this->cObj->MULTIMEDIA($multimediaConf);
				} else {
					$imgsTag[$imgKey] = $this->cObj->IMAGE($imgConf);
				}

			} else {
// TODO: Multimedia einbauen!
				$fileextension = array_pop(explode('.',$this->cObj->stdWrap('Text',array('data' => 'current:1'))));
				if (!isset($conf['multimedia'])) $conf['multimedia'] = t3lib_div::trimExplode(',','swf',true);
				if (in_array($fileextension,$conf['multimedia'])) {
					$multimediaConf['file'] = $this->cObj->stdWrap('Text',array('data' => 'current:1'));
					// $multimediaConf['params'] = '';
					$multimediaConf['params'] = isset($conf['multimedia.']['params'])?$conf['multimedia.']['params']:'';					
					$height = '';
					if (isset($imgConf['file.']) && isset($imgConf['file.']['height'])) $height = $imgConf['file.']['height']; 
					if ('' != trim($height)) {
						$multimediaConf['params'] .= ' height='.$height.' ';
					} else {
						$multimediaConf['params'] .= ' height=100% ';
					}

					if (!isset($imgConf['file.']['width.'])) {
						$width = (isset($imgConf['file.']['maxW']))?$imgConf['file.']['maxW']:'';
					} elseif(!isset($imgConf['file.']['maxW'])) {
						$width = $this->cObj->stdWrap('?',$imgConf['file.']['width.']);
					} else {
						$width = min($imgConf['file.']['maxW'],$this->cObj->stdWrap('?',$imgConf['file.']['width.']));
					}
					if ('' != trim($width)) {
						$multimediaConf['params'] .= "\n".' width='.$width.' ';
					} else {
						$multimediaConf['params'] .= "\n".' width=100% ';
					}
					$imgsTag[$imgKey] = $this->cObj->MULTIMEDIA($multimediaConf);
				} else {
					$imgsTag[$imgKey] = $this->cObj->IMAGE(Array('file' => $totalImagePath)); 	// currentValKey !!!
				}

// MH: END Changes for Multimedia
			}
				// Restore our ATagParams
			$GLOBALS['TSFE']->ATagParams = $oldATagParms;
				// Store the original filepath
			$origImages[$imgKey] = $GLOBALS['TSFE']->lastImageInfo;

			$imageRowsFinalWidths[floor($a/$colCount)] += $GLOBALS['TSFE']->lastImageInfo[0];
		}
			// How much space will the image-block occupy?
		$imageBlockWidth = max($imageRowsFinalWidths)+ $colspacing*($colCount-1) + $colCount*$border*($borderSpace+$borderThickness)*2;
		$GLOBALS['TSFE']->register['rowwidth'] = $imageBlockWidth;
		$GLOBALS['TSFE']->register['rowWidthPlusTextMargin'] = $imageBlockWidth + $textMargin;

			// noRows is in fact just one ROW, with the amount of columns specified, where the images are placed in.
			// noCols is just one COLUMN, each images placed side by side on each row
		$noRows = $this->cObj->stdWrap($conf['noRows'],$conf['noRows.']);
		$noCols = $this->cObj->stdWrap($conf['noCols'],$conf['noCols.']);
		if ($noRows) {$noCols=0;}	// noRows overrides noCols. They cannot exist at the same time.

		$rowCount_temp = 1;
		$colCount_temp = $colCount;
		if ($noRows)	{
			$rowCount_temp = $rowCount;
			$rowCount = 1;
		}
		if ($noCols)	{
			$colCount = 1;
			$columnWidths = array();
		}

			// Edit icons:
		$editIconsHTML = $conf['editIcons']&&$GLOBALS['TSFE']->beUserLogin ? $this->cObj->editIcons('',$conf['editIcons'],$conf['editIcons.']) : '';

			// If noRows, we need multiple imagecolumn wraps
		$imageWrapCols = 1;
		if ($noRows)	{ $imageWrapCols = $colCount; }

			// User wants to separate the rows, but only do that if we do have rows
		$separateRows = $this->cObj->stdWrap($conf['separateRows'], $conf['separateRows.']);
		if ($noRows)	{ $separateRows = 0; }
		if ($rowCount == 1)	{ $separateRows = 0; }

			// Apply optionSplit to the list of classes that we want to add to each image
		$addClassesImage = $conf['addClassesImage'];
		if ($conf['addClassesImage.'])	{
			$addClassesImage = $this->cObj->stdWrap($addClassesImageConf, $conf['addClassesImage.']);
		}
		$addClassesImageConf = $GLOBALS['TSFE']->tmpl->splitConfArray(array('addClassesImage' => $addClassesImage), $colCount);

			// Render the images
		$images = '';
		for ($c = 0; $c < $imageWrapCols; $c++)	{
			$tmpColspacing = $colspacing;
			if (($c==$imageWrapCols-1 && $imagePosition==2) || ($c==0 && ($imagePosition==1||$imagePosition==0))) {
					// Do not add spacing after column if we are first column (left) or last column (center/right)
				$tmpColspacing = 0;
			}

			$thisImages = '';
			$allRows = '';
			$maxImageSpace = 0;
			for ($i = $c; $i<count($imgsTag); $i=$i+$imageWrapCols)	{
				$colPos = $i%$colCount;
				if ($separateRows && $colPos == 0) {
					$thisRow = '';
				}

					// Render one image
				$imageSpace = $origImages[$i][0] + $border*($borderSpace+$borderThickness)*2;
				$GLOBALS['TSFE']->register['IMAGE_NUM'] = $i;
				$GLOBALS['TSFE']->register['IMAGE_NUM_CURRENT'] = $i;
				$GLOBALS['TSFE']->register['ORIG_FILENAME'] = $origImages[$i]['origFile'];
				$GLOBALS['TSFE']->register['imagewidth'] = $origImages[$i][0];
				$GLOBALS['TSFE']->register['imagespace'] = $imageSpace;
				$GLOBALS['TSFE']->register['imageheight'] = $origImages[$i][1];
				if ($imageSpace > $maxImageSpace)	{
					$maxImageSpace = $imageSpace;
				}
				$thisImage = '';
				$thisImage .= $this->cObj->stdWrap($imgsTag[$i], $conf['imgTagStdWrap.']);

				if ($conf['captionSplit'] || $conf['imageTextSplit'])	{
					$thisImage .= $this->cObj->stdWrap($this->cObj->cObjGet($conf['caption.'], 'caption.'), $conf['caption.']);
				}
				if ($editIconsHTML)	{
					$thisImage .= $this->cObj->stdWrap($editIconsHTML, $conf['editIconsStdWrap.']);
				}
				if ($conf['netprintApplicationLink'])	{
					$thisImage .= $this->cObj->netprintApplication_offsiteLinkWrap($thisImage, $origImages[$i], $conf['netprintApplicationLink.']);
				}
				$thisImage = $this->cObj->stdWrap($thisImage, $conf['oneImageStdWrap.']);
				$classes = '';
				if ($addClassesImageConf[$colPos]['addClassesImage'])	{
					$classes = ' ' . $addClassesImageConf[$colPos]['addClassesImage'];
				}
				$thisImage = str_replace('###CLASSES###', $classes, $thisImage);

				if ($separateRows)	{
					$thisRow .= $thisImage;
				} else {
					$allRows .= $thisImage;
				}
				$GLOBALS['TSFE']->register['columnwidth'] = $maxImageSpace + $tmpColspacing;
				if ($separateRows && ($colPos == ($colCount-1) || $i+1==count($imgsTag)))	{
					// Close this row at the end (colCount), or the last row at the final end
					$allRows .= $this->cObj->stdWrap($thisRow, $conf['imageRowStdWrap.']);
				}
			}
			if ($separateRows)	{
				$thisImages .= $allRows;
			} else {
				$thisImages .= $this->cObj->stdWrap($allRows, $conf['noRowsStdWrap.']);
			}
			if ($noRows)	{
					// Only needed to make columns, rather than rows:
				$images .= $this->cObj->stdWrap($thisImages, $conf['imageColumnStdWrap.']);
			} else {
				$images .= $thisImages;
			}
		}

			// Add the global caption, if not split
		if ($caption)	{
			$images .= $caption;
		}

			// CSS-classes
		$captionClass = '';
		$classCaptionAlign = array(
			'center' => 'csc-textpic-caption-c',
			'right' => 'csc-textpic-caption-r',
			'left' => 'csc-textpic-caption-l',
		);
		$captionAlign = $this->cObj->stdWrap($conf['captionAlign'], $conf['captionAlign.']);
		if ($captionAlign)	{
			$captionClass = $classCaptionAlign[$captionAlign];
		}
		$borderClass = '';
		if ($border)	{
			$borderClass = 'csc-textpic-border';
		}

			// Multiple classes with all properties, to be styled in CSS
		$class = '';
		$class .= ($borderClass? ' '.$borderClass:'');
		$class .= ($captionClass? ' '.$captionClass:'');
		$class .= ($equalHeight? ' csc-textpic-equalheight':'');
		$addClasses = $this->cObj->stdWrap($conf['addClasses'], $conf['addClasses.']);
		$class .= ($addClasses ? ' '.$addClasses:'');

			// Do we need a width in our wrap around images?
		$imgWrapWidth = '';
		if ($position == 0 || $position == 8)	{
				// For 'center' we always need a width: without one, the margin:auto trick won't work
			$imgWrapWidth = $imageBlockWidth;
		}
		if ($rowCount > 1)	{
				// For multiple rows we also need a width, so that the images will wrap
			$imgWrapWidth = $imageBlockWidth;
		}
		if ($caption)	{
				// If we have a global caption, we need the width so that the caption will wrap
			$imgWrapWidth = $imageBlockWidth;
		}

			// Wrap around the whole image block
		$GLOBALS['TSFE']->register['totalwidth'] = $imgWrapWidth;
		if ($imgWrapWidth)	{
			$images = $this->cObj->stdWrap($images, $conf['imageStdWrap.']);
		} else {
			$images = $this->cObj->stdWrap($images, $conf['imageStdWrapNoWidth.']);
		}

		$output = $this->cObj->cObjGetSingle($conf['layout'], $conf['layout.']);
		$output = str_replace('###TEXT###', $content, $output);
		$output = str_replace('###IMAGES###', $images, $output);
		$output = str_replace('###CLASSES###', $class, $output);

		if ($conf['stdWrap.'])	{
			$output = $this->cObj->stdWrap($output, $conf['stdWrap.']);
		}

		return $output;
	 }

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/csc_with_multimedia/class.ux_tx_cssstyledcontent_pi1.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/csc_with_multimedia/class.ux_tx_cssstyledcontent_pi1.php']);
}

?>