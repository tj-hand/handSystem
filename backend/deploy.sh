#!/bin/bash
REGISTRY="handbibackend.azurecr.io"
APP_NAME="handbi-backend"
RESOURCE_GROUP="HandBI"

# Use unique tag (optional: set TAG manually or from Git commit)
TAG=$(date +%s)
IMAGE="$REGISTRY/laravel-backend:$TAG"

# Build, tag, push
docker build -t $IMAGE .
az acr login --name ${REGISTRY%%.*}
docker push $IMAGE

az containerapp update \
  --name $APP_NAME \
  --resource-group $RESOURCE_GROUP \
  --image $IMAGE
