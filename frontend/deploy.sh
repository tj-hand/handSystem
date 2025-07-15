#!/bin/bash
REGISTRY="handbifrontend.azurecr.io"
APP_NAME="handbi-frontend"
RESOURCE_GROUP="HandBI"

# Use unique tag (optional: set TAG manually or from Git commit)
TAG=$(date +%s)
IMAGE="$REGISTRY/laravel-frontend:$TAG"

# Build, tag, push
docker build -t $IMAGE .
az acr login --name ${REGISTRY%%.*}
docker push $IMAGE

az containerapp update \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --image $IMAGE
