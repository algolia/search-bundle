In this page you will find our recommended way of installing Docker on your machine. 
This guide is made for OSX users.

## Install Docker

First install Docker using [Homebrew](https://brew.sh/)
```
$ brew install docker
```

You can then install [Docker Desktop](https://docs.docker.com/get-docker/) if you wish, or use `docker-machine`. As we prefer the second option, we will only document this one.

## Setup your Docker

Install `docker-machine`
```
$ brew install docker-machine
```

Then install [VirtualBox](https://www.virtualbox.org/) with [Homebrew Cask](https://github.com/Homebrew/homebrew-cask) to get a driver for your Docker machine
```
$ brew cask install virtualbox
```

You may need to enter your password and authorize the application in your `System Settings` > `Security & Privacy`.

Create a new machine, set it up as default and connect your shell to it.

```
$ docker-machine create --driver virtualbox default
$ docker-machine env default
$ eval "$(docker-machine env default)"
```

Now you're all set to use our Docker image!

## Build the image

```bash
docker build -t algolia-search-bundle .
```

## Run the image

You need to provide a few environment variables at runtime in order to run the [Common Test Suite](https://github.com/algolia/algoliasearch-client-specs/tree/master/common-test-suite).
You can set them up directly in the command:

```bash
docker run -it --rm --env ALGOLIA_APP_ID=XXXXXX [...] -v $PWD:/app -w /app algolia-search-bundle bash
```

However, we advise you to export them in your `.bashrc` or `.zshrc`. That way, you can use [Docker's shorten syntax](https://docs.docker.com/engine/reference/commandline/run/#set-environment-variables--e---env---env-file) to set your variables.

```bash
docker run -it --rm --env ALGOLIA_APP_ID \
                    --env ALGOLIA_API_KEY \
-v $PWD:/app -w /app algolia-search-bundle bash
```

Once your container is running, any changes you make in your IDE are directly reflected in the container.

The first time you start the container, you have to manually install the composer dependencies.
```shell script
composer update
```

As Docker and your current working directory are linked, this will also create a `vendor/` folder locally.

To launch the tests, you can use one of the following commands
```shell script
# run only the unit tests
composer test

# run a single test
./vendor/bin/phpunit --filter=nameOfYourTests
```
Once your container is running, any changes you make in your IDE are directly reflected in the container.

To launch the tests, you can use one of the following commands
