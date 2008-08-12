
There is no manual yet - i am not sure, if someone really neads an manual, please send me an mail: typo3@martinholtz.de

What does this extension:

it enables you to put even flash-files beside text with the ordinary "text with image" Content Element (textpic).

It is a hook of css_styled_content.
It makes use of $this->cObj->MULTIMEDIA with with and heigth Params

1)  You have to add the your Multimedia Image to the list of imagefiles.
If you do not, you will not be able, to add such a file to textpic (image with text)

$TYPO3_CONF_VARS['GFX']['imagefile_ext'] = 'gif,jpg,jpeg,tif,bmp,pcx,tga,png,pdf,ai,swf';

2) Install Extension, there should be nothing special

3) tell the Extension, that which file-extensions are multimedia file-extensions
tt_content.image.20.multimedia = swf, wav 
# define params for all mutlimedia objects
# width and height will be set automatically
	tt_content.image.20.multimedia = swf
tt_content.image.20.multimedia.params (
autostart=TRUE
autoplay=TRUE
CONTROLLER=true
LOOP=false
wmod="transparent"

)
# Include the last empty LINE!
# The Line-Breaks must be set for the MUTLIMEDIA Object
// Each Object will be renderd by the MULTIMEDIA Element

4) add some images and some swf-files to an image or image with text Content-Element

5) tell me (typo3@martinholtz.de) if something does not work

6) please contribute via patch (please with docs and explain it)


