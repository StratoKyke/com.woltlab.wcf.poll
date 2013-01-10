<ol class="pollResultList">
	{foreach from=$poll->getOptions() item=option}
		<li class="pollResultItem">
			<span class="pollMeter" style="width: {if $option->getRelativeVotes($poll)}{@$option->getRelativeVotes($poll)}%{else}0{/if}">&nbsp;</span>
			<div class="caption">
				<span class="optionName">
					{$option->optionValue} ({#$option->votes})</span>
					<span class="relativeVotes">{@$option->getRelativeVotes($poll)}%</span>
				</span>
			</div>
		</li>
	{/foreach}
</ol>

{if $poll->isPublic}
	<div class="formSubmit">
		<button class="jsPollShowParticipants small">{lang}wcf.poll.button.showParticipants{/lang}</button>
	</div>
{/if}