<?php
/**
 *  This file is part of the Magero Packager.
 *
 *  (c) Magero team <support@magero.pw>
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Magero\Packager;

use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use ArrayObject;
use SimpleXMLElement;

/**
 * Class Config
 *
 * @package Magero\Packager
 */
class Config
{
    /** @var string */
    private $name;

    /** @var string */
    private $version;

    /** @var string */
    private $stability;

    /** @var string */
    private $license;

    /** @var string */
    private $channel;

    /** @var string */
    private $summary;

    /** @var string */
    private $description;

    /** @var string */
    private $notes;

    /** @var array */
    private $authors = [];

    /** @var string */
    private $phpMinVersion;

    /** @var string */
    private $phpMaxVersion;

    /** @var array */
    private $requiredPackages = [];

    /**
     * @param $file
     *
     * @return Config
     */
    public static function createFromFile($file)
    {
        if (!is_readable($file)) {
            throw new InvalidArgumentException('Unreadable file');
        }

        return new self(Yaml::parse(file_get_contents($file)));
    }

    /**
     * Config constructor.
     *
     * @param array $data
     */
    public function __construct(array $data)
    {
        if (empty($data)) {
            throw new InvalidArgumentException('Empty config data');
        }

        if (empty($data['name']) || (strlen(trim($data['name'])) == 0)) {
            throw new InvalidArgumentException('Invalid package name');
        }
        $name = trim($data['name']);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/i', $name)) {
            throw new InvalidArgumentException(sprintf('Invalid package name "%s"', $name));
        }
        $this->name = $name;

        if (isset($data['version'])) {
            if (strlen(trim($data['version'])) == 0) {
                throw new InvalidArgumentException('Invalid package version');
            }
            $version = trim($data['version']);
            if (!preg_match('/^\d+\.\d+(\.\d+)+$/i', $version)) {
                throw new InvalidArgumentException(sprintf('Invalid package version "%s"', $version));
            }
            $this->version = $version;
        }

        if (empty($data['stability']) || (strlen(trim($data['stability'])) == 0)) {
            throw new InvalidArgumentException('Invalid package stability');
        }
        $stability = trim($data['stability']);
        if (!in_array($stability, ['alpha', 'beta', 'stable'])) {
            throw new InvalidArgumentException(sprintf('Invalid package stability "%s"', $stability));
        }
        $this->stability = $stability;

        if (empty($data['license']) || (strlen(trim($data['license'])) == 0)) {
            throw new InvalidArgumentException('Invalid package license');
        }
        $this->license = trim($data['license']);

        if (empty($data['channel']) || (strlen(trim($data['channel'])) == 0)) {
            throw new InvalidArgumentException('Invalid package channel');
        }
        $this->channel = trim($data['channel']);

        if (empty($data['summary']) || (strlen(trim($data['summary'])) == 0)) {
            throw new InvalidArgumentException('Invalid package summary');
        }
        $this->summary = trim($data['summary']);

        if (empty($data['description']) || (strlen(trim($data['description'])) == 0)) {
            throw new InvalidArgumentException('Invalid package description');
        }
        $this->description = trim($data['description']);

        if (!empty($data['notes'])) {
            $this->notes = trim($data['notes']);
        }

        if (empty($data['authors']) || !is_array($data['authors'])) {
            throw new InvalidArgumentException('Invalid package authors');
        }
        $authors = $data['authors'];
        foreach ($authors as $author) {
            if (empty($author['name']) || (strlen(trim($author['name'])) == 0)) {
                throw new InvalidArgumentException('Invalid package author name');
            }
            $authorName = trim($author['name']);

            if (empty($author['user']) || (strlen(trim($author['user'])) == 0)) {
                throw new InvalidArgumentException('Invalid package author user');
            }
            $authorUser = trim($author['user']);
            if (!preg_match('/^[a-zA-Z0-9_-]+$/i', $authorUser)) {
                throw new InvalidArgumentException(sprintf('Invalid package author user "%s"', $authorUser));
            }

            if (empty($author['email']) || (strlen(trim($author['email'])) == 0)) {
                throw new InvalidArgumentException('Invalid package author email');
            }
            $authorEmail = trim($author['email']);
            if (!preg_match('/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/i', $authorEmail)) {
                throw new InvalidArgumentException(sprintf('Invalid package author email "%s"', $authorEmail));
            }

            $this->authors[] = [
                'name' => $authorName,
                'user' => $authorUser,
                'email' => $authorEmail,
            ];
        }

        if (empty($data['php_min_version']) || (strlen(trim($data['php_min_version'])) == 0)) {
            throw new InvalidArgumentException('Invalid package php min version');
        }
        $version = trim($data['php_min_version']);
        if (!preg_match('/^\d+\.\d+\.\d+$/i', $version)) {
            throw new InvalidArgumentException(sprintf('Invalid package php min version "%s"', $version));
        }
        $this->phpMinVersion = $version;

        if (empty($data['php_max_version']) || (strlen(trim($data['php_max_version'])) == 0)) {
            throw new InvalidArgumentException('Invalid package php max version');
        }
        $version = trim($data['php_max_version']);
        if (!preg_match('/^\d+\.\d+\.\d+$/i', $version)) {
            throw new InvalidArgumentException(sprintf('Invalid package php max version "%s"', $version));
        }
        $this->phpMaxVersion = $version;

        $requiredPackages = !empty($data['required_packages']) ? $data['required_packages'] : [];
        foreach ($requiredPackages as $requiredPackage) {
            if (empty($requiredPackage['name']) || (strlen(trim($requiredPackage['name'])) == 0)) {
                throw new InvalidArgumentException('Invalid required package name');
            }
            $requiredPackageName = trim($requiredPackage['name']);

            if (empty($requiredPackage['channel']) || (strlen(trim($requiredPackage['channel'])) == 0)) {
                throw new InvalidArgumentException(
                    sprintf('Invalid required package "%s" channel', $requiredPackageName)
                );
            }
            $requiredPackageChannel = trim($requiredPackage['channel']);

            $requiredPackageMinVersion = null;
            if (isset($requiredPackage['min'])) {
                $requiredPackageMinVersion = trim($requiredPackage['min']) ?: null;
                if ($requiredPackageMinVersion && !preg_match('/^\d+(\.\d+)+$/i', $requiredPackageMinVersion)) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid required package min version "%s"', $requiredPackageMinVersion)
                    );
                }
            }

            $requiredPackageMaxVersion = null;
            if (isset($requiredPackage['max'])) {
                $requiredPackageMaxVersion = trim($requiredPackage['max']) ?: null;
                if ($requiredPackageMaxVersion && !preg_match('/^\d+(\.\d+)+$/i', $requiredPackageMaxVersion)) {
                    throw new InvalidArgumentException(
                        sprintf('Invalid required package max version "%s"', $requiredPackageMaxVersion)
                    );
                }
            }

            $this->requiredPackages[] = [
                'name' => $requiredPackageName,
                'channel' => $requiredPackageChannel,
                'min' => $requiredPackageMinVersion,
                'max' => $requiredPackageMaxVersion,
            ];
        }
    }

    /**
     * @return string
     */
    public function getPackageFileName()
    {
        return $this->name . ($this->version ? '-' . $this->version : '');
    }

    /**
     * @param SimpleXMLElement $xmlElement
     * @param $childName
     * @return null|SimpleXMLElement
     */
    private function getXmlChild($xmlElement, $childName)
    {
        /** @var SimpleXMLElement $child */
        foreach ($xmlElement->children() as $child) {
            if ($child->getName() == $childName) {
                return $child;
            }
        }

        return null;
    }

    /**
     * @param SimpleXMLElement $packageXml
     *
     * @return $this
     */
    public function configurePackage($packageXml)
    {
        $this->getXmlChild($packageXml, 'name')[0] = $this->name;
        if ($this->version) {
            $this->getXmlChild($packageXml, 'version')[0] = $this->version;
        }
        $this->getXmlChild($packageXml, 'stability')[0] = $this->stability;
        $this->getXmlChild($packageXml, 'license')[0] = $this->license;
        $this->getXmlChild($packageXml, 'channel')[0] = $this->channel;
        $this->getXmlChild($packageXml, 'summary')[0] = $this->summary;
        $this->getXmlChild($packageXml, 'description')[0] = $this->description;
        if ($this->notes) {
            $this->getXmlChild($packageXml, 'notes')[0] = $this->notes;
        }

        $authorsXml = $this->getXmlChild($packageXml, 'authors');
        foreach ($this->authors as $author) {
            $authorXml = $authorsXml->addChild('author');
            foreach ($author as $key => $value) {
                $authorXml->addChild($key)[0] = $value;
            }
        }

        $this->getXmlChild($packageXml, 'date')[0] = date('Y-m-d');
        $this->getXmlChild($packageXml, 'time')[0] = date('H:i:s');

        $requiredXml = $this->getXmlChild($packageXml, 'dependencies')->addChild('required');
        $phpXml = $requiredXml->addChild('php');
        $phpXml->addChild('min')[0] = $this->phpMinVersion;
        $phpXml->addChild('max')[0] = $this->phpMaxVersion;

        foreach ($this->requiredPackages as $packageData) {
            $packageXml = $requiredXml->addChild('package');
            foreach ($packageData as $key => $value) {
                $packageXml->addChild($key)[0] = $value;
            }
        }

        return $this;
    }

    /**
     * @param SimpleXMLElement $packageXml
     * @param string $code
     * @param ArrayObject $filesStructure
     *
     * @return $this
     */
    public function configurePackageContents($packageXml, $code, $filesStructure)
    {
        $target = $this->getXmlChild($packageXml, 'contents')->addChild('target');
        $target->addAttribute('name', $code);

        foreach ($filesStructure['directories'] as $directory) {
            $this->buildXmlDir($target, $directory);
        }
        foreach ($filesStructure['files'] as $file) {
            $this->buildXmlFile($target, $file, $filesStructure['real_path']);
        }

        return $this;
    }

    /**
     * @param SimpleXMLElement $parentXmlElement
     * @param ArrayObject $parentDirectory
     *
     * @return $this
     */
    private function buildXmlDir($parentXmlElement, $parentDirectory)
    {
        $xmlDir = $parentXmlElement->addChild('dir');
        $xmlDir->addAttribute('name', $parentDirectory['name']);

        foreach ($parentDirectory['directories'] as $directory) {
            $this->buildXmlDir($xmlDir, $directory);
        }
        foreach ($parentDirectory['files'] as $file) {
            $this->buildXmlFile($xmlDir, $file, $parentDirectory['real_path']);
        }

        return $this;
    }

    /**
     * @param SimpleXMLElement $parentXmlElement
     * @param string $file
     * @param string $directoryRealPath
     *
     * @return $this
     */
    private function buildXmlFile($parentXmlElement, $file, $directoryRealPath)
    {
        $xmlFile = $parentXmlElement->addChild('file');
        $xmlFile->addAttribute('name', $file);
        $xmlFile->addAttribute('hash', md5_file($directoryRealPath . '/' . $file));

        return $this;
    }
}
