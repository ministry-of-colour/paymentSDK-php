#!/bin/bash
set -e # Exit with nonzero exit code if anything fails

SOURCE_BRANCH="master"
TARGET_BRANCH="gh-pages"
UPLOAD_DIRECTORY="gh-pages-upload-tmp"

# Save some useful information
REPO=`git config remote.origin.url`
SSH_REPO=${REPO/https:\/\/github.com\//git@github.com:}
SHA=`git rev-parse --verify HEAD`

# Clone the existing gh-pages for this repo into ${UPLOAD_DIRECTORY}/
# Create a new empty branch if gh-pages doesn't exist yet (should only happen on first deploy)
git clone -q $REPO ${UPLOAD_DIRECTORY}
cd ${UPLOAD_DIRECTORY}
git checkout ${TARGET_BRANCH} || git checkout --orphan ${TARGET_BRANCH}
cd ..

# Clean UPLOAD_DIRECTORY existing contents
rm -rf ${UPLOAD_DIRECTORY}/**/* || exit 0

## Compile the docs
# Download apigen
wget -q http://apigen.org/apigen.phar

# generate the reference
php -f apigen.phar -- generate -s src -d ${UPLOAD_DIRECTORY}/docs --template-theme="bootstrap"

# Now let's go have some fun with the cloned repo
cd ${UPLOAD_DIRECTORY}
git config user.name "Travis CI"
git config user.email "wirecard@travis-ci.org"

# If there are no changes to the compiled ${UPLOAD_DIRECTORY} (e.g. this is a README update) then just bail.
!(git diff --exit-code --quiet) || {
    echo "No changes to the output on this push; exiting."
    exit 0
}

# Commit the "changes", i.e. the new version.
# The delta will show diffs between new and old versions.
git add --all .
git commit -m "Deploy documentation to GitHub Pages: ${SHA}"

# Get the deploy key by using Travis's stored variables to decrypt deploy_key.enc
openssl aes-256-cbc -K $encrypted_72a07e6f0049_key -iv $encrypted_72a07e6f0049_iv -in ../deploy_key.enc -out deploy_key -d
chmod 600 deploy_key
eval `ssh-agent -s`
ssh-add deploy_key

# Now that we're all set up, we can push.
git push $SSH_REPO $TARGET_BRANCH