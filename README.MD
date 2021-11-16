# Ourvoice Data Portal
[![Build Status](https://travis-ci.com/susom/ourvoice_admin.svg?token=vSNpxxGBXSBYMFjziTQk&branch=travis)](https://travis-ci.com/susom/ourvoice_admin)

<img alt="PHP" src="https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white"/>
<img alt="Google Cloud" src="https://img.shields.io/badge/GoogleCloud-%234285F4.svg?style=for-the-badge&logo=google-cloud&logoColor=white"/>
<img alt="TravisCI" src="https://img.shields.io/badge/travisci-%232B2F33.svg?style=for-the-badge&logo=travis&logoColor=white"/>

#

## Detail
The Ourvoice Data portal 

The Ourvoice data portal currently runs in parallel to the django flask dashboard.   It provides an interface for end-users to upload and adjust walk data.

## Local Development
This repository contains a docker-compose file pre-configured for local deployment. After obtaining the secrets folder from one of the maintainers of this project, simply run `docker-compose up` and navigate to `localhost:8201`

## Deployment

The ourvoice data portal is currently integrated with TravisCI & deployed via Cloud Run automatically.

Any commits to the dev and main branches will be built to configuration & deployed as services on cloud run.

Secrets are injected at deploy time and stored within GCloud.

Ensure the OIDC Redirect URI matches the network endpoint specified in the load balancer.




## Firestore Storage
This app uses Firestore in Google.  For our current instance, we have:
|  Container | Purpose |
|  ov_projects    |  key is project_id > contents are project metadata 
|  ov_walks       |  key is combo guid of project_id + _ + device_uid + _ + walk_timestamp >  contents is object with device, photo, survey results, etc...
|  dev_ov_projects | dev version
|  dev_ov_walks    | dev version

The Photos array in the data is critical:
- array
  - audios
    geotag
    


contains photo and audio files for a walk.  Files are organized in the container as :  project_code > device_uid > unix_timestamp for walk > raw upload files (e.g. photo_0.jpg, photo_1.jpg, audio_0.wav, audio_1.wav) |




## Bucket Storage
| Instance | Container | Purpose |
| production  | ov_walk_files     | contains photo and audio files for a walk.  Files are organized in the container as :  project_code > device_uid > unix_timestamp for walk > raw upload files (e.g. photo_0.jpg, photo_1.jpg, audio_0.wav, audio_1.wav) |
| development | dev_ov_walk_files | dev version of ov_walk_files |


### Audio files
Default is .wav or .amr format.  Goal should be to reduce audio files.
