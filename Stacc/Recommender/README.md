# STACC Recommender Magento 2.x extension

# Installation

1. Navigate to your magento installation folder

2. Add the Composer repository
    
    ```
    composer config repositories.stacc-recommender vcs git@bitbucket.org:dasso/magento2_extension.git
    ```

3. Require the installation
    
    ```
    GIT_SSH_COMMAND='ssh -i key_file' composer require stacc/recommender:dev-master
    ```

4. Enable the extension
    
    ```
    bin/magento module:enable Stacc_Recommender --clear-static-content
    ```
    
5. Register the extension
    
    ```
    bin/magento setup:upgrade
    ```

6. Recompile Magento
    
    ```
    bin/magento setup:di:compile
    ```

7. Verify that the extension is enabled
    
    ```
    bin/magento module:status
    ```
