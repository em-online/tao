<ul class="qti-navigator-parts plain">
    {{#each parts}}
    <li class="qti-navigator-part collapsible {{#if active}}active{{else}}collapsed{{/if}}" data-id="{{id}}">
        <span class="qti-navigator-label" title="{{label}}">
            <span class="qti-navigator-text">{{label}}</span>
            <span class="icon-up"></span>
            <span class="icon-down"></span>
        </span>
        {{#if isLinear}}
        <div class="qti-navigator-linear-part collapsible-panel">
            <span class="icon icon-info" title="{{__ 'In this part of the test navigation is not allowed.'}}"></span>
            <p class="qti-navigator-message">
                {{__ 'In this part of the test navigation is not allowed.'}}
            </p>
            <p class="qti-navigator-actions">
                <button class="btn-info small" data-position="{{position}}" title="{{__ 'Start Test-part'}}">
                    <span class="qti-navigator-text">{{__ 'Start Test-part'}}</span>
                    <span class="icon-play r"></span>
                </button>
            </p>
        </div>
        {{else}}
        <ul class="qti-navigator-sections collapsible-panel plain">
            {{#each sections}}
            <li class="qti-navigator-section collapsible {{#if active}}active{{else}}collapsed{{/if}}" data-id="{{id}}">
                <span class="qti-navigator-label" title="{{label}}">
                    <span class="qti-navigator-text">{{label}}</span>
                </span>
                <div class="qti-navigator-items collapsible-panel plain">
                    {{#each items}}
                    <div class="qti-navigator-item {{cls}}" data-id="{{id}}" data-position="{{position}}" style="margin: 2px; padding-left: 0px">
                        <span class="qti-navigator-label truncate" title="{{label}}" style="justify-content: center; line-height:60px;">
                        {{label}}
                        </span>
                    </div>
                    {{/each}}
                </div>
            </li>
            {{/each}}
        </ul>
        {{/if}}
    </li>
    {{/each}}
</ul>
