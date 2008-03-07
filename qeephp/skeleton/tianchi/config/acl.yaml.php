# <?php die(); ?>

#############################
# 访问规则
#############################

#
# 访问规则示例
#

# 控制器名称
nonexistent:
  # 对该控制器需要的访问权限
  allow: acl_everyone
  # actions 表示对控制器的个别动作进行权限控制
  actions:
    first:
      # first 动作的访问权限
      allow: acl_everyone
    second:
      # second 动作的访问权限
      deny: member
    # action_all 代表该控制器的所有其他动作
    action_all:
      allow: member
