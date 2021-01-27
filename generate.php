<?php
/*
 * Dockerfile generator.
 *
 * MIT License. Copyright (c) 2021. Riccardo De Martis
 * All Trademarks referred to are the property of their respective owners.
 */

$versions = [
    'R2019a' => ['v9.6'],
    'R2019b' => ['v9.7'],
    'R2020a' => ['v9.8'],
];

$builds = [
//    'R2019a' => range(3,9)
//    'R2019a' => [22,[3,'2020-Nov-16'] ]
    'R2019a' => [[3,'2019-nov-06']]

];

/**
 * @param string $vers // v9.7
 * @param string $named_vers // R2019b
 * @param string $date // creation date: ex: 2020-Nov-16
 * @param int    $update_vers // 6
 * @param string $ld_lib_ver // v97
 * @return string
 */
function gen_dockerfile($vers, $named_vers, $date, $update_vers, $ld_lib_ver)
{
    $dockerfile = <<<EOF
# MATLAB Compiler Runtime (MCR) $vers ($named_vers)
#
# This docker file will configure an environment into which the Matlab compiler
# runtime will be installed and in which stand-alone matlab routines (such as
# those created with MATLAB's deploytool) can be executed.

# MATLAB Runtime
# Run compiled MATLAB applications or components without installing MATLAB
# The MATLAB Runtime is a standalone set of shared libraries that enables the
# execution of compiled MATLAB applications or components. When used together,
# MATLAB, MATLAB Compiler, and the MATLAB Runtime enable you to create and distribute
# numerical applications or software components quickly and securely.
#
# See https://www.mathworks.com/products/compiler/matlab-runtime.html for more info.
#
# @author Riccardo De Martis $date
#

FROM debian:stretch-slim
MAINTAINER Riccardo De Martis <riccardo@demartis.it>
ENV DEBIAN_FRONTEND noninteractive

RUN apt-get -q update && \
    apt-get install -q -y --no-install-recommends \
      xorg \
      unzip \
      wget \
      curl && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/*

# Download the MCR from MathWorks site an install with -mode silent
RUN mkdir /mcr-install && \
    mkdir /opt/mcr && \
    cd /mcr-install && \
    wget --no-check-certificate -q https://ssd.mathworks.com/supportfiles/downloads/$named_vers/Release/$update_vers/deployment_files/installer/complete/glnxa64/MATLAB_Runtime_${named_vers}_Update_${update_vers}_glnxa64.zip && \
    unzip -q MATLAB_Runtime_${named_vers}_Update_${update_vers}_glnxa64.zip && \
    rm -f MATLAB_Runtime_${named_vers}_Update_${update_vers}_glnxa64.zip && \
    ./install -destinationFolder /opt/mcr -agreeToLicense yes -mode silent && \
    cd / && \
    rm -rf mcr-install

# Configure environment variables for MCR
ENV LD_LIBRARY_PATH /opt/mcr/$ld_lib_ver/runtime/glnxa64:/opt/mcr/$ld_lib_ver/bin/glnxa64:/opt/mcr/$ld_lib_ver/sys/os/glnxa64:/opt/mcr/$ld_lib_ver/extern/bin/glnxa64

ENV XAPPLRESDIR /etc/X11/app-defaults
EOF;

    return $dockerfile;
}

foreach($builds as $version=>$build){

    $d_vers = $versions[$version][0];
    $d_name = $version;
    $d_libv = str_replace('.', '', $d_vers);

    foreach($build as $update) {

        if(is_array($update)){
            $d_updv = $update[0];
            $d_date = "\n# @creation $update[1]";
        }else{
            $d_updv = $update;
            $d_date = '';
        }

        $dockerfile = gen_dockerfile($d_vers, $d_name, $d_date, $d_updv, $d_libv);

        $folder_name = $d_name.'-u'.$d_updv;
        if (!file_exists($folder_name)) {
            mkdir($folder_name, 0777, true);
        }
        file_put_contents($folder_name.DIRECTORY_SEPARATOR.'Dockerfile', $dockerfile);
    }
}