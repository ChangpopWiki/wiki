variable "GIT_SHA" {}
variable "VERSION" {}

variable "IMAGES" {
  default = ["mediawiki", "setup", "backup"]
}

group "default" {
  targets = IMAGES
}

target "images" {
  name     = item
  inherits = ["_common"]
  matrix = {
    item = IMAGES
  }
  cache-from = ["type=gha,scope=${item}"]
  cache-to   = ["type=gha,scope=${item},mode=max"]
  tags = [
    "ghcr.io/changpopwiki/wiki-${item}:latest",
    "ghcr.io/changpopwiki/wiki-${item}:${GIT_SHA}",
    "ghcr.io/changpopwiki/wiki-${item}:${VERSION}"
  ]
}

target "_common" {
  platforms = ["linux/amd64", "linux/arm64"]
}