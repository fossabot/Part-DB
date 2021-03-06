<div class="row">
    <div class="col-md-6">
        {if isset($can_delete) && isset($can_edit)}
            <div class="select_actions" style="display: none;">
                <input type="hidden" name="selected_ids" value="">
                <div class="form-inline">
                    <div class="form-group">
                        <span class="label label-primary"><span class="selected_n">10</span> {t}Bauteile:{/t}</span>
                    </div>
                    <div class="form-group">
                        <select name="action" style="width: 110px;" class="form-control">
                            <option value="">{t}Auswählen{/t}</option>
                            <option value="delete" {if !$can_delete}disabled{/if}>{t}Löschen{/t}</option>
                            <option value="move" {if !$can_edit}disabled{/if}>{t}Verschieben nach{/t}</option>
                            <option value="favor" {if !isset($can_favor) || !$can_favor}disabled{/if}>{t}Bauteile favorisieren{/t}</option>
                            <option value="defavor" {if !isset($can_favor) || !$can_favor}disabled{/if}>{t}Favorisierung aufheben{/t}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <select name="target" style="width: 110px;" class="selectpicker" data-live-search="true" {if !$can_edit}disabled{/if}>
                            <option>{t}Auswählen{/t}</option>
                            {if isset($categories_list)}
                                <optgroup label="{t}Kategorien{/t}">
                                    {$categories_list nofilter}
                                </optgroup>
                            {/if}
                            {if isset($footprints_list)}
                                <optgroup label="{t}Footprint{/t}">
                                    {$footprints_list nofilter}
                                </optgroup>
                            {/if}
                            {if isset($storelocations_list)}
                                <optgroup label="{t}Lagerort{/t}">
                                    {$storelocations_list nofilter}
                                </optgroup>
                            {/if}
                            {if isset($manufacturers_list)}
                                <optgroup label="{t}Hersteller{/t}">
                                    {$manufacturers_list nofilter}
                                </optgroup>
                            {/if}
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary" name="multi_action">Ok</button>
                </div>
            </div>
        {/if}
    </div>
    <div class="col-md-6">
        <nav aria-label="Page navigation" class="pull-right">
            <ul class="pagination" style="margin-top: 0; margin-bottom: 5px;">
                <li class="disabled"><a class="no-progbar">{$pagination.lower_result}-{$pagination.upper_result}/{$pagination.max_entries}</a></li>
                {foreach $pagination.entries as $page}
                    <li {if isset($page.disabled) && $page.disabled}class="disabled" {/if}
                            {if isset($page.active) && $page.active}class="active"{/if}>
                        <a {if !isset($page.disabled) || !$page.disabled}href="{$page.href}{/if}"
                           {if isset($page.hint)}title="{$page.hint}" {/if}
                        >{$page.label nofilter}</a></li>
                {/foreach}
                <li class="disabled"><select name="limit" onchange="submitForm(this.form);">
                        <option value="25" {if $limit == 25}selected{/if}>25</option>
                        <option value="50" {if $limit == 50}selected{/if}>50</option>
                        <option value="100" {if $limit == 100}selected{/if}>100</option>
                        <option value="150" {if $limit == 150}selected{/if}>125</option>
                        <option value="200" {if $limit == 200}selected{/if}>200</option>
                        <option value="250" {if $limit == 250}selected{/if}>250</option>
                    </select></li>
            </ul>
        </nav>
    </div>
</div>