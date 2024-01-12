<template>
    <ul class="chat">
        <li class="left clearfix" v-for="message in messages">
            <div class="chat-body clearfix">
                Lorem ipsum dolor sit amet, consectetur adipisicing elit.
                 Deserunt modi dolor et consequuntur ad dolores! Nulla pariatur blanditiis, 
                 quae hic dignissimos eos omnis sequi, id aut voluptatem veritatis illum explicabo.
                <div class="header">
                    <strong class="primary-font">
                        {{ message.user.name }}
                    </strong>
                </div>
                <p>
                    {{ message.message }}
                </p>
            </div>
        </li>
    </ul>
</template>

<script>
  export default {
    props: ['messages']
  };
</script>