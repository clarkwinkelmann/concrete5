<? defined('C5_EXECUTE') or die("Access Denied.");

use \Concrete\Core\Page\Type\Composer\FormLayoutSetControl as PageTypeComposerFormLayoutSetControl;
use \Concrete\Core\Page\Type\Composer\FormLayoutSet as PageTypeComposerFormLayoutSet;

?>

<div style="display: none">

	<div id="ccm-page-type-composer-add-set" class="ccm-ui">
		<form method="post" action="<?=$view->action('add_set', $pagetype->getPageTypeID())?>">
			<?=Loader::helper('validation/token')->output('add_set')?>
			<div class="form-group">
				<?=$form->label('ptComposerFormLayoutSetName', tc('Name of a set', 'Set Name'))?>
				<?=$form->text('ptComposerFormLayoutSetName')?>
			</div>
			<div class="form-group">
				<?=$form->label('ptComposerFormLayoutSetDescription', tc('Descripmove-set-controltion of a set', 'Set Description'))?>
				<?=$form->textarea('ptComposerFormLayoutSetDescription')?>
			</div>
		</form>
		<div class="dialog-buttons">
			<button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?=t('Cancel')?></button>
			<button class="btn btn-primary pull-right" onclick="$('#ccm-page-type-composer-add-set form').submit()"><?=t('Add Set')?></button>
		</div>
	</div>
</div>

<div class="ccm-dashboard-header-buttons btn-group">
	<a href="#" data-dialog="add_set" class="btn btn-default"><?=t('Add Set')?></a>
    <a href="<?=URL::to('/dashboard/pages/types')?>" class="btn btn-default"><?=t('Back to List')?></a>
</div>
<div class="ccm-pane-body ccm-pane-body-footer">

<p class="lead"><?php echo $pagetype->getPageTypeDisplayName(); ?></p>

<? if (count($sets) > 0) {

	/* @var $set Concrete\Core\Page\Type\Composer\FormLayoutSet */
	foreach($sets as $set) { ?>

		<div class="ccm-page-type-composer-form-layout-control-set panel panel-default" data-page-type-composer-form-layout-control-set-id="<?= $set->getPageTypeComposerFormLayoutSetID()?>">
			<div class="panel-heading">
				<ul class="ccm-page-type-composer-item-controls">
					<li><a href="<?=REL_DIR_FILES_TOOLS_REQUIRED?>/page_types/composer/form/add_control?ptComposerFormLayoutSetID=<?=$set->getPageTypeComposerFormLayoutSetID()?>" dialog-title="<?=t('Add Form Control')?>" dialog-width="640" dialog-height="400" data-command="add-form-set-control"><i class="fa fa-plus"></i></a></li>
					<li><a href="#" data-command="move_set" style="cursor: move"><i class="fa fa-arrows"></i></a></li>
					<li><a href="#" data-edit-set="<?=$set->getPageTypeComposerFormLayoutSetID()?>"><i class="fa fa-pencil"></i></a></li>
					<li><a href="#" data-delete-set="<?=$set->getPageTypeComposerFormLayoutSetID()?>"><i class="fa fa-trash-o"></i></a></li>
				</ul>
				<div class="ccm-page-type-composer-form-layout-control-set-name" ><?
					if ($set->getPageTypeComposerFormLayoutSetDisplayName()) {
						echo $set->getPageTypeComposerFormLayoutSetDisplayName();
					} else {
						echo t('(No Name)');
					}
				?></div>
			</div>

			<div style="display: none">
				<div data-delete-set-dialog="<?=$set->getPageTypeComposerFormLayoutSetID()?>">
					<form data-delete-set-form="<?=$set->getPageTypeComposerFormLayoutSetID()?>" action="<?=$view->action('delete_set', $set->getPageTypeComposerFormLayoutSetID())?>" method="post">
						<?=t("Delete this form layout set? This cannot be undone.")?>
						<?=Loader::helper('validation/token')->output('delete_set')?>
					</form>
					<div class="dialog-buttons">
						<button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?=t('Cancel')?></button>
						<button class="btn btn-danger pull-right" onclick="$('form[data-delete-set-form=<?=$set->getPageTypeComposerFormLayoutSetID()?>]').submit();"><?=t('Delete Set')?></button>
					</div>
				</div>
			</div>

			<div style="display: none">
				<div data-edit-set-dialog="<?=$set->getPageTypeComposerFormLayoutSetID()?>" class="ccm-ui">
					<form data-edit-set-form="<?=$set->getPageTypeComposerFormLayoutSetID()?>" action="<?=$view->action('update_set', $set->getPageTypeComposerFormLayoutSetID())?>" method="post">
					<div class="form-group">
						<?=$form->label('ptComposerFormLayoutSetName', tc('Name of a set', 'Set Name'))?>
						<?=$form->text('ptComposerFormLayoutSetName', $set->getPageTypeComposerFormLayoutSetName())?>
					</div>
					<div class="form-group">
						<?=$form->label('ptComposerFormLayoutSetDescription', tc('Description of a set', 'Set Description'))?>
						<?=$form->textarea('ptComposerFormLayoutSetDescription', $set->getPageTypeComposerFormLayoutSetDescription())?>
					</div>
					<div class="dialog-buttons">
						<button class="btn btn-default pull-left" onclick="jQuery.fn.dialog.closeTop()"><?=t('Cancel')?></button>
						<button class="btn btn-primary pull-right" onclick="$('form[data-edit-set-form=<?=$set->getPageTypeComposerFormLayoutSetID()?>]').submit();"><?=t('Update Set')?></button>
					</div>
					<?=Loader::helper('validation/token')->output('update_set')?>
					</form>
				</div>
			</div>

			<table class="table table-hover" style="width: 100%;">
				<tbody class="ccm-page-type-composer-form-layout-control-set-inner">
					<? $controls = PageTypeComposerFormLayoutSetControl::getList($set);
					foreach($controls as $cnt) {
						echo Loader::element('page_types/composer/form/layout_set/control', array('control' => $cnt));
					} ?>
				</tbody>
			</table>
		</div>

	<? } ?>
<? } else { ?>
	<p><?=t('You have not added any composer form layout control sets.')?></p>
<? } ?>

</div>


<script type="text/javascript">

var Composer = {

    deleteFromLayoutSetControl: function(ptComposerFormLayoutSetControlID) {
        jQuery.fn.dialog.showLoader();
        var formData = [{
            'name': 'token',
            'value': '<?=Loader::helper("validation/token")->generate("delete_set_control")?>'
        }, {
            'name': 'ptComposerFormLayoutSetControlID',
            'value': ptComposerFormLayoutSetControlID
        }];

        $.ajax({
            type: 'post',
            data: formData,
            url: '<?=$view->action("delete_set_control")?>',
            success: function() {
                jQuery.fn.dialog.hideLoader();
                jQuery.fn.dialog.closeAll();
                $('tr[data-page-type-composer-form-layout-control-set-control-id=' + ptComposerFormLayoutSetControlID + ']').remove();
            }
        });
    }

}

$(function() {
	$('a[data-dialog=add_set]').on('click', function() {
		jQuery.fn.dialog.open({
			element: '#ccm-page-type-composer-add-set',
			modal: true,
			width: 320,
			title: '<?=t("Add Control Set")?>',
			height: 'auto'
		});
	});
	$('a[data-delete-set]').on('click', function() {
		var ptComposerFormLayoutSetID = $(this).attr('data-delete-set');
        jQuery.fn.dialog.open({
            element: 'div[data-delete-set-dialog=' + ptComposerFormLayoutSetID + ']',
            modal: true,
            width: 320,
            title: '<?=t("Delete Control Set")?>',
            height: 'auto'
        });
	});
	$('a[data-edit-set]').on('click', function() {
		var ptComposerFormLayoutSetID = $(this).attr('data-edit-set');
        jQuery.fn.dialog.open({
            element: 'div[data-edit-set-dialog=' + ptComposerFormLayoutSetID + ']',
            modal: true,
            width: 320,
            title: '<?=t("Update Control Set")?>',
            height: 'auto'
        });
	});
	$('div.ccm-pane-body').sortable({
		handle: 'a[data-command=move_set]',
		items: '.ccm-page-type-composer-form-layout-control-set',
		cursor: 'move',
		axis: 'y',
		stop: function() {
			var formData = [{
				'name': 'token',
				'value': '<?=Loader::helper("validation/token")->generate("update_set_display_order")?>'
			}, {
				'name': 'ptID',
				'value': <?=$pagetype->getPageTypeID()?>
			}];
			$('.ccm-page-type-composer-form-layout-control-set').each(function() {
				formData.push({'name': 'ptComposerFormLayoutSetID[]', 'value': $(this).attr('data-page-type-composer-form-layout-control-set-id')});
			});
			$.ajax({
				type: 'post',
				data: formData,
				url: '<?=$view->action("update_set_display_order")?>',
				success: function() {

				}
			});
		}
	});
	$('a[data-command=add-form-set-control]').dialog();
	$('a[data-command=edit-form-set-control]').dialog();

	$('.ccm-page-type-composer-form-layout-control-set-inner').sortable({
		handle: 'a[data-command=move-set-control]',
		items: '.ccm-page-type-composer-form-layout-control-set-control',
		cursor: 'move',
		axis: 'y',
		helper: function(e, ui) { // prevent table columns from collapsing
			ui.addClass('active');
			ui.children().each(function () {
				$(this).width($(this).width());
			});
			return ui;
		},
		stop: function(e, ui) {
			ui.item.removeClass('active');

			var formData = [{
				'name': 'token',
				'value': '<?=Loader::helper("validation/token")->generate("update_set_control_display_order")?>'
			}, {
				'name': 'ptComposerFormLayoutSetID',
				'value': $(this).parent().parent().attr('data-page-type-composer-form-layout-control-set-id')
			}];

			$(this).find('.ccm-page-type-composer-form-layout-control-set-control').each(function() {
				formData.push({'name': 'ptComposerFormLayoutSetControlID[]', 'value': $(this).attr('data-page-type-composer-form-layout-control-set-control-id')});
			});

			$.ajax({
				type: 'post',
				data: formData,
				url: '<?=$view->action("update_set_control_display_order")?>',
				success: function() {}
			});

		}
	});

	$('.ccm-page-type-composer-form-layout-control-set-inner').on('click', 'a[data-delete-set-control]', function() {
		var ptComposerFormLayoutSetControlID = $(this).attr('data-delete-set-control');
        jQuery.fn.dialog.open({
            element: 'div[data-delete-set-control-dialog=' + ptComposerFormLayoutSetControlID + ']',
            modal: true,
            width: 320,
            title: '<?=t("Delete Control")?>',
            height: 'auto'
        });
		return false;
	});

});
</script>

<style type="text/css">

div.ccm-page-type-composer-form-layout-control-set {
	margin-top: 20px;
}

div.ccm-page-type-composer-form-layout-control-set:last-child {
	margin-bottom: 20px;
}

.panel-heading .ccm-page-type-composer-item-controls {
	float: right;
}

ul.ccm-page-type-composer-item-controls a {
	color: #333;
}

ul.ccm-page-type-composer-item-controls a i {
	position: relative;
}

ul.ccm-page-type-composer-item-controls a:hover {
	text-decoration: none;
}

ul.ccm-page-type-composer-item-controls {
	padding: 0;
	margin: 0;
	width: 60px;
}

.panel-heading:hover > .ccm-page-type-composer-item-controls li,
.ccm-page-type-composer-form-layout-control-set-control:hover .ccm-page-type-composer-item-controls li {
	display: inline-block;
}

.ccm-page-type-composer-item-controls li {
	display: none;
	list-style-type: none;
}

th, td {
	padding-left: 15px !important;
	padding-right: 15px !important;
}

</style>
