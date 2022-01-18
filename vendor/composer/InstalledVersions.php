<?php


namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;


class InstalledVersions
{
    private static $installed = array(
        'root' =>
            array(
                'pretty_version' => 'dev-master',
                'version' => 'dev-master',
                'aliases' =>
                    array(),
                'reference' => 'd231b3e2344be241b31bf56ddc7661c45a2634ea',
                'name' => '__root__',
            ),
        'versions' =>
            array(
                '__root__' =>
                    array(
                        'pretty_version' => 'dev-master',
                        'version' => 'dev-master',
                        'aliases' =>
                            array(),
                        'reference' => 'd231b3e2344be241b31bf56ddc7661c45a2634ea',
                    ),
                'kigkonsult/icalcreator' =>
                    array(
                        'pretty_version' => 'v2.29.25',
                        'version' => '2.29.25.0',
                        'aliases' =>
                            array(),
                        'reference' => 'e757e2fae2b5c89fe9abbc648a4e0943dc7a8c8b',
                    ),
                'monolog/monolog' =>
                    array(
                        'pretty_version' => '1.25.5',
                        'version' => '1.25.5.0',
                        'aliases' =>
                            array(),
                        'reference' => '1817faadd1846cd08be9a49e905dc68823bc38c0',
                    ),
                'phpmailer/phpmailer' =>
                    array(
                        'pretty_version' => 'v6.1.7',
                        'version' => '6.1.7.0',
                        'aliases' =>
                            array(),
                        'reference' => '2c2370ba3df7034f9eb7b8f387c97b52b2ba5ad0',
                    ),
                'psr/log' =>
                    array(
                        'pretty_version' => '1.1.3',
                        'version' => '1.1.3.0',
                        'aliases' =>
                            array(),
                        'reference' => '0f73288fd15629204f9d42b7055f72dacbe811fc',
                    ),
                'psr/log-implementation' =>
                    array(
                        'provided' =>
                            array(
                                0 => '1.0.0',
                            ),
                    ),
                'twilio/sdk' =>
                    array(
                        'pretty_version' => '5.42.2',
                        'version' => '5.42.2.0',
                        'aliases' =>
                            array(),
                        'reference' => '0cfcb871b18a9c427dd9e8f0ed7458d43009b48a',
                    ),
            ),
    );
    private static $canGetVendors;
    private static $installedByVendor = array();


    public static function getInstalledPackages()
    {
        $packages = array();
        foreach (self::getInstalled() as $installed) {
            $packages[] = array_keys($installed['versions']);
        }


        if (1 === \count($packages)) {
            return $packages[0];
        }

        return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
    }


    public static function isInstalled($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (isset($installed['versions'][$packageName])) {
                return true;
            }
        }

        return false;
    }


    public static function satisfies(VersionParser $parser, $packageName, $constraint)
    {
        $constraint = $parser->parseConstraints($constraint);
        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));

        return $provided->matches($constraint);
    }


    public static function getVersionRanges($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            $ranges = array();
            if (isset($installed['versions'][$packageName]['pretty_version'])) {
                $ranges[] = $installed['versions'][$packageName]['pretty_version'];
            }
            if (array_key_exists('aliases', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
            }
            if (array_key_exists('replaced', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
            }
            if (array_key_exists('provided', $installed['versions'][$packageName])) {
                $ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
            }

            return implode(' || ', $ranges);
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }


    public static function getVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['version'])) {
                return null;
            }

            return $installed['versions'][$packageName]['version'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }


    public static function getPrettyVersion($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['pretty_version'])) {
                return null;
            }

            return $installed['versions'][$packageName]['pretty_version'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }


    public static function getReference($packageName)
    {
        foreach (self::getInstalled() as $installed) {
            if (!isset($installed['versions'][$packageName])) {
                continue;
            }

            if (!isset($installed['versions'][$packageName]['reference'])) {
                return null;
            }

            return $installed['versions'][$packageName]['reference'];
        }

        throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
    }


    public static function getRootPackage()
    {
        $installed = self::getInstalled();

        return $installed[0]['root'];
    }


    public static function getRawData()
    {
        return self::$installed;
    }


    public static function reload($data)
    {
        self::$installed = $data;
        self::$installedByVendor = array();
    }


    private static function getInstalled()
    {
        if (null === self::$canGetVendors) {
            self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
        }

        $installed = array();

        if (self::$canGetVendors) {

            foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
                if (isset(self::$installedByVendor[$vendorDir])) {
                    $installed[] = self::$installedByVendor[$vendorDir];
                } elseif (is_file($vendorDir . '/composer/installed.php')) {
                    $installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir . '/composer/installed.php';
                }
            }
        }

        $installed[] = self::$installed;

        return $installed;
    }
}
