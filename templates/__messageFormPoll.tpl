{if $__showPoll|isset && $__showPoll}
	<script type="text/javascript" src="{@$__wcf->getPath()}js/WCF.Poll.js"></script>
	<script type="text/javascript">
		//<![CDATA[
		$(function() {
			WCF.Language.addObject({
				'wcf.poll.button.addOption': '{lang}wcf.poll.button.addOption{/lang}',
				'wcf.poll.button.removeOption': '{lang}wcf.poll.button.removeOption{/lang}'
			});
			
			new WCF.Poll.Management('pollOptionContainer', [ {implode from=$pollOptions item=pollOption}{ optionID: {@$pollOption[optionID]}, optionValue: '{$pollOption[optionValue]}' }{/implode} ]);
		});
		//]]>
	</script>
	
	<div id="poll" class="jsOnly tabMenuContent container containerPadding">
		<fieldset>
			<dl{if $errorField == 'pollOptions'} class="formError"{/if}>
				<dt>
					<label for="pollQuestion">{lang}wcf.poll.question{/lang}</label>
				</dt>
				<dd>
					<input type="text" name="pollQuestion" id="pollQuestion" value="{$pollQuestion}" class="long" />
				</dd>
				<dt>
					<label>{lang}wcf.poll.options{/lang}</label>
				</dt>
				<dd id="pollOptionContainer" class="sortableListContainer">
					<ol class="sortableList"></ol>
					{if $errorField == 'pollOptions'}
						<small class="innerError">
							{lang}wcf.global.form.error.empty{/lang}
						</small>
					{/if}
					<small>{lang}wcf.poll.options.description{/lang}</small>
				</dd>
			</dl>
			<dl>
				<dt{if $errorField == 'pollEndTime'} class="formError"{/if}>
					<label for="pollEndTime">{lang}wcf.poll.endTime{/lang}</label>
				</dt>
				<dd>
					<input type="datetime" name="pollEndTime" id="pollEndTime" value="{@$pollEndTime}" />
					{if $errorField == 'pollEndTime'}
						<small class="innerError">
							{lang}wcf.poll.endTime.error.{@$errorType}{/lang}
						</small>
					{/if}
				</dd>
			</dl>
			<dl{if $errorField == 'pollMaxVotes'} class="formError"{/if}>
				<dt>
					<label for="pollMaxVotes">{lang}wcf.poll.maxVotes{/lang}</label>
				</dt>
				<dd>
					<input type="number" name="pollMaxVotes" id="pollMaxVotes" value="{@$pollMaxVotes}" min="1" />
					{if $errorField == 'pollMaxVotes'}
						<small class="innerError">
							{lang}wcf.poll.endTime.error.{@$errorType}{/lang}
						</small>
					{/if}
				</dd>
				<dd>
					<label><input type="checkbox" name="pollIsChangeable" value="1"{if $pollIsChangeable} checked="checked"{/if} /> {lang}wcf.poll.isChangeable{/lang}</label>
				</dd>
				{if !$pollID}
					<dd>
						<label><input type="checkbox" name="pollIsPublic" value="1"{if $pollIsPublic} checked="checked"{/if} /> {lang}wcf.poll.isPublic{/lang}</label>
					</dd>
				{/if}
				<dd>
					<label><input type="checkbox" name="pollResultsRequireVote" value="1"{if $pollResultsRequireVote} checked="checked"{/if} /> {lang}wcf.poll.resultsRequireVote{/lang}</label>
					<small>{lang}wcf.poll.resultsRequireVote.description{/lang}</small>
				</dd>
				<dd>
					<label><input type="checkbox" name="pollSortByVotes" value="1"{if $pollSortByVotes} checked="checked"{/if} /> {lang}wcf.poll.sortByVotes{/lang}</label>
				</dd>
			</dl>
		</fieldset>
	</div>
{/if}