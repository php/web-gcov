<?php

if(mail('quadra23@accesscomm.ca', 'test?', 'some message'))
	echo 'mail sent';
else
	echo 'mail failed';
