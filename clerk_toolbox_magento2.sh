#!/usr/bin/env bash
echo "-----------------------------------------"
echo "- Welcome To Clerk.io Magento 2 Toolbox -"
echo "-----------------------------------------"

case "$1" in
	-i|--install)
        if [ -z "$2" ];
        then
            shift
                echo "-------------------------------------------"
                echo "- Installing Clerk.io Magento 2 Extension -"
                echo "-------------------------------------------"

                echo "-------------------"
                echo "- Creating Backup -"
                echo "-------------------"
                tar -czf clerkbackup.tar.gz . --exclude=./*.gz;
                echo "---------------------"
                echo "- Backup Completed! -"
                echo "---------------------"

                echo ""
                echo "-------------------------------"
                echo "- Downloading Clerk Extension -"
                echo "-------------------------------"
                COMPOSER_MEMORY_LIMIT=5G composer require clerk/magento2;
                echo "-----------------------"
                echo "- Download Completed! -"
                echo "-----------------------"

                echo ""
                echo "------------------------------"
                echo "- Installing Clerk Extension -"
                echo "------------------------------"
                php -d memory_limit=5G bin/magento module:enable Clerk_Clerk;
                echo "----------------------"
                echo "- Install Completed! -"
                echo "----------------------"

                echo ""
                echo "-----------------------"
                echo "- Magento 2 Upgrading -"
                echo "-----------------------"
                php -d memory_limit=5G bin/magento setup:upgrade;
                echo "------------------------"
                echo "- Upgrading Completed! -"
                echo "------------------------"

                echo ""
                echo "------------------"
                echo "- Clearing Cache -"
                echo "------------------"
                php -d memory_limit=5G bin/magento setup:di:compile;
                echo "-----------------------------"
                echo "- Clearing Cache Completed! -"
                echo "-----------------------------"

                echo ""
                echo "------------------------------------------------"
                echo "- DONE! Clerk.io Extension is now ready to use -"
                echo "------------------------------------------------"
            shift
        else
            version="$2"
            shift
                echo "-------------------------------------------"
                echo "- Installing Clerk.io Magento 2 Extension -"
                echo "-------------------------------------------"

                echo "-------------------"
                echo "- Creating Backup -"
                echo "-------------------"
                tar -czf clerkbackup.tar.gz . --exclude=./*.gz;
                echo "---------------------"
                echo "- Backup Completed! -"
                echo "---------------------"

                echo ""
                echo "-------------------------------"
                echo "- Downloading Clerk Extension -"
                echo "-------------------------------"
                COMPOSER_MEMORY_LIMIT=5G composer require clerk/magento2 "$version";
                echo "-----------------------"
                echo "- Download Completed! -"
                echo "-----------------------"

                echo ""
                echo "------------------------------"
                echo "- Installing Clerk Extension -"
                echo "------------------------------"
                php -d memory_limit=5G bin/magento module:enable Clerk_Clerk;
                echo "----------------------"
                echo "- Install Completed! -"
                echo "----------------------"

                echo ""
                echo "-----------------------"
                echo "- Magento 2 Upgrading -"
                echo "-----------------------"
                php -d memory_limit=5G bin/magento setup:upgrade;
                echo "------------------------"
                echo "- Upgrading Completed! -"
                echo "------------------------"

                echo ""
                echo "------------------"
                echo "- Clearing Cache -"
                echo "------------------"
                php -d memory_limit=5G bin/magento setup:di:compile;
                echo "-----------------------------"
                echo "- Clearing Cache Completed! -"
                echo "-----------------------------"

                echo ""
                echo "------------------------------------------------"
                echo "- DONE! Clerk.io Extension is now ready to use -"
                echo "------------------------------------------------"
            shift
        fi
        ;;

	-u|--uninstall)
		shift
			echo "---------------------------------------------"
			echo "- Uninstalling Clerk.io Magento 2 Extension -"
			echo "---------------------------------------------"
			php -d memory_limit=5G bin/magento module:disable Clerk_Clerk;
			COMPOSER_MEMORY_LIMIT=5G composer remove clerk/magento2;
			echo "---------------------------"
			echo "- Uninstalling Completed! -"
			echo "---------------------------"

			echo ""
			echo "-----------------------"
			echo "- Magento 2 Upgrading -"
			echo "-----------------------"
			php -d memory_limit=5G bin/magento setup:upgrade;
			echo "------------------------"
			echo "- Upgrading Completed! -"
			echo "------------------------"

			echo ""
			echo "------------------"
			echo "- Clearing Cache -"
			echo "------------------"
			php -d memory_limit=5G bin/magento setup:di:compile;
			echo "-----------------------------"
			echo "- Clearing Cache Completed! -"
			echo "-----------------------------"

			echo ""
			echo "-----------------------------------------------"
			echo "- DONE! Clerk.io Extension is now Uninstalled -"
			echo "-----------------------------------------------"
		shift
		;;
	-r|--restore)
		shift
			echo "--------------------------"
			echo "- Rebuilding From Backup -"
			echo "--------------------------"
			tar -zxvf clerkbackup.tar.gz;
			echo "-------------------------"
			echo "- Rebuilding Completed! -"
			echo "-------------------------"
		shift
		;;
	-b|--backup)
		shift
			echo "-------------------"
			echo "- Creating Backup -"
			echo "-------------------"
			tar -czf clerkbackup.tar.gz . --exclude=./*.gz;
			echo "---------------------"
			echo "- Backup Completed! -"
			echo "---------------------"
		shift
		;;
	*)
		echo "-------------------"
		echo "- Toolbox Options -"
		echo "-------------------"
		echo "-i, 	--install 					Installing Clerk.io Magento 2 Extension"
		echo "-iv, 	--installversion 					Installing Specific Version Of Clerk.io Magento 2 Extension"
		echo "-u, 	--uninstall 					Uninstalling Clerk.io Magento 2 Extension"
		echo "-r, 	--restore 					Restore from the backup"
		echo "-b, 	--backup 					Make full backup of Magento 2"
		exit 0
		break
		;;
esac