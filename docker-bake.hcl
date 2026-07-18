variable "GIT_SHA" {
  default = "dev"
}

group "default" {
  targets = ["mediawiki", "setup", "backup"]
}

target "_common" {
  platforms = ["linux/amd64", "linux/arm64"]
  cache-from = ["type=gha"]
  cache-to   = ["type=gha,mode=max"]
}

target "mediawiki" {
  inherits = ["_common"]
  tags = [
    "ghcr.io/changpopwiki/wiki-mediawiki:latest",
    "ghcr.io/changpopwiki/wiki-mediawiki:${GIT_SHA}",
  ]
}

target "setup" {
  inherits = ["_common"]
  tags = [
    "ghcr.io/changpopwiki/wiki-setup:latest",
    "ghcr.io/changpopwiki/wiki-setup:${GIT_SHA}",
  ]
}

target "backup" {
  inherits = ["_common"]
  tags = [
    "ghcr.io/changpopwiki/wiki-backup:latest",
    "ghcr.io/changpopwiki/wiki-backup:${GIT_SHA}",
  ]
}