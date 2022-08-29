#Rollback and migrate specific items
ddev drush mr upgrade_d7_node_type
ddev drush mr upgrade_d7_user_role
ddev drush mr upgrade_d7_user

ddev drush mim upgrade_d7_node_type
ddev drush mim upgrade_d7_user_role
ddev drush mim upgrade_d7_user
