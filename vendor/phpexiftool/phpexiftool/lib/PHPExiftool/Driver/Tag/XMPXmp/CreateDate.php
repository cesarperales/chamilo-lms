<?php

/*
 * This file is part of PHPExifTool.
 *
 * (c) 2012 Romain Neutron <imprec@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPExiftool\Driver\Tag\XMPXmp;

use JMS\Serializer\Annotation\ExclusionPolicy;
use PHPExiftool\Driver\AbstractTag;

/**
 * @ExclusionPolicy("all")
 */
class CreateDate extends AbstractTag
{

    protected $Id = 'CreateDate';

    protected $Name = 'CreateDate';

    protected $FullName = 'XMP::xmp';

    protected $GroupName = 'XMP-xmp';

    protected $g0 = 'XMP';

    protected $g1 = 'XMP-xmp';

    protected $g2 = 'Image';

    protected $Type = 'date';

    protected $Writable = true;

    protected $Description = 'Create Date';

    protected $local_g2 = 'Time';

}
