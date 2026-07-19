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
  labels = {
    "org.opencontainers.image.title" = "wiki-${item}"
    "org.opencontainers.image.version" = VERSION
    "org.opencontainers.image.revision" = GIT_SHA
    "org.opencontainers.image.source" = "https://github.com/ChangpopWiki/wiki"
    "org.opencontainers.image.vendor" = "ChangpopWiki"
    "org.opencontainers.image.description" = "창팝위키 미디어위키 스택에서 ${item} 서비스에 사용되는 커스텀 이미지입니다."
  }
}

target "_common" {
  platforms = ["linux/amd64", "linux/arm64"]
}