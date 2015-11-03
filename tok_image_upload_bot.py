#! /usr/bin/env python3
# -*- coding: utf-8; mode: Python; folded-file: t -*-
# Time-stamp: <2015-09-18 18:12:50 torsten>

# {{{ preamble

import os
from PIL import Image, ImageFilter
import re
import requests
import sys
import tempfile
import time
import tkinter as tk
import tkinter.font as tkFont
# }}}

# {{{ Configuration section

# {{{ online credentials

# Just strings inside quotation marks, remove the brackets too
Txp_Login_URL = '[[TXP_Login_URL]]'
Txp_Userid = '[[TXP_User_Id]]'
Txp_Password = '[[TXP_Password]]'
# }}}

# {{{ new images settings

Image_Size = (800, 800)
Thumbnail_Size = (150, 150)
Default_Category = ''
# }}}

# {{{ caption grabber

# caption_grabber tuple: start point for search, regex to extract content, end point for search

# For Xmp.dc.description
caption_grabber = '<dc:description>', '<rdf:li .*?">(.*)</rdf:li>', '</dc:description>'

# For Exif.UserComment
# caption_grabber = '<exif:UserComment>', '<rdf:li .*?">(.*)</rdf:li>', '</exif:UserComment>'

# }}}

# {{{ files and dirs

TempDir = tempfile.mkdtemp( prefix='tok_image_upload_bot_' )
Thumbnail_Suffix = '_tn'

# }}}

# }}}

# {{{ Class GUI

class Program():
    def __init__( self ):
        root = tk.Tk( className = "tokUploadBot" )
        # variable for program control
        self.TriggerContinue = tk.BooleanVar()
        self.TriggerContinue.set( False )
        # upper frame setup
        upperFrame = tk.Frame( root, relief = "raised" )
        upperFrame.pack( fill = "both", expand = True )
        textbox = tk.Text( upperFrame, width = 50, height = 10)
        textbox.pack( side = "left", fill = "both", expand = True)
        # font setup
        bold_font = tkFont.Font( textbox, textbox.cget( "font" ) )
        bold_font.configure( weight = "bold" )
        textbox.tag_configure( 'highlightline',
                               font = bold_font )
        textbox.tag_configure( 'warnline',
                               font = bold_font,
                               foreground = "#ff1234" )
        # lower frame init
        lowerFrame = tk.Frame( root, relief = "raised" )
        lowerFrame.pack( fill = "both", expand = True )

        def log( text ):
            textbox.insert( tk.END, text )
            textbox.see( tk.END )
            textbox.update()

        def boldlog( text ):
            textbox.insert( tk.END, text, 'highlightline' )
            textbox.see( tk.END )
            textbox.update()

        def warnlog( text ):
            textbox.insert( tk.END, text, 'warnline' )
            textbox.see( tk.END )
            textbox.update()

        def triggerContinue():
            self.TriggerContinue.set( True )

        def fail( errorCode ):
            self.TriggerContinue.set( False )
            button = tk.Button(lowerFrame, text="shame", command = triggerContinue )
            button.pack( side="right", padx = 5, pady = 5 )
            root.update()
            root.wait_variable( self.TriggerContinue )
            sys.exit( errorCode )

# {{{ Create image and thumbnail for upload

        def createImageFiles():
            log( "Resizing image ...\n" )
            upload_image = Image.open( current_input_file )
            try:
                upload_image.thumbnail( Image_Size, Image.ANTIALIAS )
                if upload_image.mode == "RGB":
                    upload_image = upload_image.filter( ImageFilter.SHARPEN )
                upload_image.save( Upload_Image_File )
            except:
                log( "Creating of " + Upload_Image_File + " failed. Exiting!" )
                fail( 5 )

            log( "Creating thumbnail …\n" )
            upload_thumb = Image.open( Upload_Image_File )
            try:
                upload_thumb.thumbnail( Thumbnail_Size, Image.ANTIALIAS )
                if upload_thumb.mode == "RGB":
                    upload_image = upload_image.filter( ImageFilter.SHARPEN )
                upload_thumb.save( Upload_Thumbnail_File )
            except:
                log( "Creating of " + Upload_Thumbnail_File + " failed. Exiting!" )
                fail( 6 )

# }}}

# {{{ Upload image files

        def uploadImageFiles( pic_category, Default_Category, pic_caption ):
            log( "Uploading image …\n" )

            params = {
                'MAX_FILE_SIZE': '8388608',
                'event': 'image',
                'step': 'image_insert',
                'id': '',
                'sort': '',
                'dir': '',
                'page': '',
                'search_method': '',
                'crit': '',
                '_txp_token': txp_token }

            files = { 'thefile': open( Upload_Image_File, 'rb' ) }
            image_id = ''
            
            try:
                req = requests.post( Txp_Login_URL,
                                     data = params,
                                     files = files,
                                     cookies = txp_cookies )

                # get id of current image
                i = re.search( '\n<input type="hidden" value="([0-9]+)" name="id" />\n',
                               str( req.text ) )
                if i:
                    image_id = i.group(1)
            
            except:
                log( "Upload of image failed. Exiting!" )
                fail( 8 )

            # Get image ID
            boldlog( "Image ID is: " + image_id + "\n" )

            log( "Uploading thumbnail …\n" )

            params = {
                'MAX_FILE_SIZE': '8388608',
                'event': 'image',
                'step': 'thumbnail_insert',
                'id': image_id,
                'sort': 'id',
                'dir': 'desc',
                'page': '1',
                'search_method': '',
                'crit': '',
                '_txp_token': txp_token }

            files = { 'thefile': open( Upload_Thumbnail_File, 'rb' ) }
            
            try:
                req = requests.post( Txp_Login_URL,
                                     data = params,
                                     files = files,
                                     cookies = txp_cookies )

            except:
                log( "Upload of thumbnail failed. Exiting." )
                fail( 9 )



            # get id of current image
            image_name = ''
            n = re.search( '<input type="text" value="(.*)" name="name" size="32" id="image_name" />',
                           str( req.text ) )
            if n:
                image_name = n.group(1)

            log( 'Setting Category and Caption …\n' )

            # {{{ Check, if (by name of directory) given category is valid

            # (means: extists as select option in HTML form)
            # grab appropriate part of HTML page
            category_select_start_pos = req.text.find( '<select id="image_category"  name="category">')
            category_select_end_pos = req.text.find( '</select>', category_select_start_pos + 20 )
            category_select_part = req.text[ category_select_start_pos:category_select_end_pos]
            # search for an option with matching value
            category_is_valid = category_select_part.find( '<option value="' + pic_category +'">' )
            # if not found ...
            if category_is_valid == -1:
                # ... use default category
                pic_category = Default_Category
                log( 'Setting default category\n' )

            # }}}
                
            if pic_caption == '':
                boldlog( 'Image has no caption.\n' )
            
            # prepare request
            params = {
                'name': image_name,
                'category': pic_category, # pic_category
                'caption': pic_caption,
                'id': image_id,
                'event': 'image',
                'step': 'image_save',
                'sort': 'id',
                'dir': 'desc',
                'page': '1',
                'search_method': '',
                'crit': '',
                '_txp_token': txp_token }

            try:
                # send request
                req = requests.post( Txp_Login_URL,
                                     data = params,
                                     cookies = txp_cookies )
            except:
                log( "Setting category and caption of image failed!\n" )

# }}}

# {{{ Clean up

        def cleanUp():
            # remove temporary files
            log( "Removing temporary files …\n" )
            os.remove( Upload_Image_File )
            os.remove( Upload_Thumbnail_File )
# }}}

# }}}
            
# {{{ Program

        # {{{ perform some checks first

        # Get Values from Commandline
        if len( sys.argv ) > 1:
            input_files = sys.argv[ 1: ]
        else:
            log( "Please provide at least one file. Quitting." )
            fail( 1 )

        # Check if Online credentials have been configured
        if Txp_Login_URL is '[[TXP_Login_URL]]' or \
                Txp_Userid is '[[TXP_User_Id]]' or \
                Txp_Password is '[[TXP_Password]]' :
            log( "Please configure your online credentials\n" + \
                     "in the configuration section\n" + \
                     "in the beginning of this script.\nExiting now." )
            fail( 2 )
        # }}}

        # {{{ login to textpattern backend (which is: get cookies and _txp_token value)

        try:
            req = requests.post( Txp_Login_URL,
                                 data = { 'p_userid': Txp_Userid,
                                          'p_password': Txp_Password,
                                          'stay': '' } )

            # get cookie
            txp_cookies = req.cookies

            # get token of txp session
            t = re.search( '\n\s+_txp_token: "([a-f0-9]*)",\n',
                           str( req.text ) )
            if t:
                txp_token = t.group(1)
            
        except:
            log( "Could not login to textpattern. Exiting!" )
            fail( 7 )
        # }}}

        # {{{ loop over given files

        for current_input_file in input_files:

            # Does the given input file exist?
            if not os.path.isfile( current_input_file ):
                log( "Given file does not exist. Exiting." )
                fail( 3 )

            this_pic = Image.open( current_input_file )

            # get image caption from image meta data
            this_pic.caption = ''
            metadata = open( current_input_file, encoding='utf-8', errors='ignore' ).read()
            desc_start = metadata.find( caption_grabber[ 0 ] )
            desc_end = metadata.find( caption_grabber[ 2 ] )
            desc_str = metadata[ desc_start:desc_end ]
            description = ''
            i = re.search( caption_grabber[ 1 ], str( desc_str ) )
            if i:
                this_pic.caption = i.group(1)

            # get image category from name of directory
            this_pic.category =  os.path.realpath( current_input_file ).split( os.sep )[ -2 ]
        
            # begin output
            boldlog( 'Processing file: ' + os.path.basename( current_input_file ) + "\n" )

            # Setup filenames
            Upload_Image_File = os.path.join(TempDir, os.path.basename( current_input_file ))
            Upload_Thumbnail_File = os.path.join(TempDir,
                                                 os.path.splitext( os.path.basename( current_input_file ))[0] + \
                                                 Thumbnail_Suffix + \
                                                 os.path.splitext( os.path.basename( current_input_file ))[1] )

            # do necessary stuff
            createImageFiles()
            uploadImageFiles( this_pic.category, Default_Category, this_pic.caption )
            
            # cleanUp()
            log( "\nDone." )
            time.sleep( 3 )
            log( "\n\n\n" )

        # done
        sys.exit()
# }}}
        
# }}}

# {{{ Entry Point
program = Program()
tk.mainloop()
# }}}
