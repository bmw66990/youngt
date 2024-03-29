<?php
namespace ots\protocolbuffer;

// @@protoc_insertion_point(namespace:.ots.protocolbuffer.UpdateRowResponse)

/**
 * Generated by the protocol buffer compiler.  DO NOT EDIT!
 * source: ots_protocol_buffer.proto
 *
 * -*- magic methods -*-
 *
 * @method \ots\protocolbuffer\ConsumedCapacity getConsumed()
 * @method void setConsumed(\ots\protocolbuffer\ConsumedCapacity $value)
 */
class UpdateRowResponse extends \ProtocolBuffers\Message
{
  // @@protoc_insertion_point(traits:.ots.protocolbuffer.UpdateRowResponse)
  
  /**
   * @var \ots\protocolbuffer\ConsumedCapacity $consumed
   * @tag 1
   * @label required
   * @type \ProtocolBuffers::TYPE_MESSAGE
   **/
  protected $consumed;
  
  
  // @@protoc_insertion_point(properties_scope:.ots.protocolbuffer.UpdateRowResponse)

  // @@protoc_insertion_point(class_scope:.ots.protocolbuffer.UpdateRowResponse)

  /**
   * get descriptor for protocol buffers
   * 
   * @return \ProtocolBuffersDescriptor
   */
  public static function getDescriptor()
  {
    static $descriptor;
    
    if (!isset($descriptor)) {
      $desc = new \ProtocolBuffers\DescriptorBuilder();
      $desc->addField(1, new \ProtocolBuffers\FieldDescriptor(array(
        "type"     => \ProtocolBuffers::TYPE_MESSAGE,
        "name"     => "consumed",
        "required" => true,
        "optional" => false,
        "repeated" => false,
        "packable" => false,
        "default"  => null,
        "message" => '\ots\protocolbuffer\ConsumedCapacity',
      )));
      // @@protoc_insertion_point(builder_scope:.ots.protocolbuffer.UpdateRowResponse)

      $descriptor = $desc->build();
    }
    return $descriptor;
  }

}
