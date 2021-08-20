<div class="qti-final-review" align="center">



    <div class="qti-final-review-info" >
        <div class="qti-final-review-answered" title="{{__ 'Completed items'}}">
                    <div class="qti-final-review-color-info">
                        <div class="qti-final-review-background-color"></div>
                        <span class="qti-final-review-info-text">{{__ 'Answered'}}</span>
                    </div>
        </div>
        <div class="qti-final-review-unanswered" title="{{__ 'Unanswered items'}}">
                    <div class="qti-final-review-color-info">
                        <div class="qti-final-review-background-color"></div>
                        <span class="qti-final-review-info-text">{{__ 'Unanswered'}}</span>
                    </div>
        </div>
    </div>




    <ul class="qti-final-review-parts plain">
        {{#each parts}}
        <li class="qti-final-review-part collapsible {{#if active}}active{{else}}collapsed{{/if}}" data-id="{{id}}">
            <span class="qti-final-review-label" title="{{label}}">
                <span class="qti-final-review-text">{{label}}</span>
                <span class="icon-up"></span>
                <span class="icon-down"></span>
            </span>
            {{#if isLinear}}
            <div class="qti-final-review-linear-part collapsible-panel">
                <span class="icon icon-info" title="{{__ 'In this part of the test navigation is not allowed.'}}"></span>
                <p class="qti-final-review-message">
                    {{__ 'In this part of the test navigation is not allowed.'}}
                </p>
                <p class="qti-final-review-actions">
                    <button class="btn-info small" data-position="{{position}}" title="{{__ 'Start Test-part'}}">
                        <span class="qti-final-review-text">{{__ 'Start Test-part'}}</span>
                        <span class="icon-play r"></span>
                    </button>
                </p>
            </div>
            {{else}}
            <ul class="qti-final-review-sections collapsible-panel plain">
                {{#each sections}}
                <li class="qti-final-review-section collapsible {{#if active}}active{{else}}collapsed{{/if}}" data-id="{{id}}">
                    <span class="qti-final-review-label" title="{{label}}">
                        <span class="qti-final-review-text">{{label}}</span>
                    </span>
                    <div class="qti-final-review-items collapsible-panel plain" >
                        {{#each items}}
                        <div class="qti-final-review-item {{cls}}" data-id="{{id}}" data-position="{{position}}" style="margin: 2px; padding-left: 0px">
                            <span class="qti-final-review-label truncate" title="{{label}}" style="justify-content: center; line-height:60px;">
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
</div>
